<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationLearner;
use App\Mail\InvitationPC;
use App\Models\Department;
use App\Models\Group;
use App\Models\User;

class AdminUserController extends Controller
{
    /* =========================================================
     |  INDEX — list all non-admin users
     | ========================================================= */

    public function index()
    {
        $authUser = Auth::user();

        $users = User::where('role', '!=', 'A')
            ->orderBy('name')
            ->get();

        $departments = Department::orderBy('name')->get(['department_id', 'name']);

        $usedGroupIds = DB::table('user_group')->pluck('group_id')->unique();
        $cohorts      = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);

        return view('Dashboard.UserManagement', compact('authUser', 'users', 'departments', 'cohorts'));
    }

    /* =========================================================
     |  STORE — create a new user and return credentials as JSON
     | ========================================================= */

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'nullable|email|max:255',
            'department_id' => 'nullable|string',
            'role'          => 'required|in:L,PC',
            'send_email'    => 'nullable|boolean',
        ]);

        $name     = trim($request->name);
        $username = $this->generateUsername($name);
        $password = $this->generatePassword();

        $user = User::create([
            'user_id'              => $username,
            'name'                 => $name,
            'email'                => $request->email,
            'department_id'        => $request->department_id ?: null,
            'role'                 => $request->role,
            'password'             => Hash::make($password),
            'must_change_password' => true,
        ]);

        $emailSent   = false;
        $emailError  = null;

        if ($request->boolean('send_email') && $user->email) {
            [$emailSent, $emailError] = $this->dispatchInvitationEmail(
                $user->email, $name, $username, $password, $request->role
            );
        }

        return response()->json([
            'success'     => true,
            'user_id'     => $user->id,
            'username'    => $username,
            'password'    => $password,
            'name'        => $name,
            'role'        => $request->role,
            'email_sent'  => $emailSent,
            'email_error' => $emailError,
        ]);
    }

    /* =========================================================
     |  UPDATE ROLE — toggle between L and PC
     | ========================================================= */

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:L,PC']);

        $oldRole = $user->role;
        $user->update(['role' => $request->role]);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->withProperties(['old_role' => $oldRole, 'new_role' => $request->role, 'ip' => $request->ip()])
            ->log('User role changed');

        return response()->json(['success' => true, 'role' => $user->role]);
    }

    /* =========================================================
     |  SEND INVITATION — (re-)generate credentials for existing user
     | ========================================================= */

    public function sendInvitation(Request $request, User $user)
    {
        $password = $this->generatePassword();

        $user->update([
            'password'             => Hash::make($password),
            'must_change_password' => true,
        ]);

        $emailSent  = false;
        $emailError = null;

        if ($request->boolean('send_email') && $user->email) {
            [$emailSent, $emailError] = $this->dispatchInvitationEmail(
                $user->email, $user->name, $user->user_id, $password, $user->role
            );
        }

        return response()->json([
            'success'     => true,
            'username'    => $user->user_id,
            'password'    => $password,
            'name'        => $user->name,
            'email_sent'  => $emailSent,
            'email_error' => $emailError,
        ]);
    }

    /* =========================================================
     |  DESTROY — remove a user account
     | ========================================================= */

    public function destroy(Request $request, User $user)
    {
        if ($user->role === 'A') {
            return response()->json(['success' => false, 'message' => 'Cannot delete an admin account.'], 403);
        }

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['deleted_user_id' => $user->id, 'deleted_user_name' => $user->name, 'ip' => $request->ip()])
            ->log('User deleted');

        $user->delete();

        return response()->json(['success' => true]);
    }

    /* =========================================================
     |  COHORT LEARNERS — returns iSpring learners for a cohort
     | ========================================================= */

    public function cohortLearners(Request $request)
    {
        $groupId = $request->input('group_id');

        $cohortUserIds = DB::table('user_group')
            ->where('group_id', $groupId)
            ->pluck('user_id')
            ->toArray();

        if (empty($cohortUserIds)) {
            return response()->json(['learners' => []]);
        }

        $ispringUsers = DB::table('users_ispring as u')
            ->leftJoin('departments as d', 'u.department_id', '=', 'd.department_id')
            ->whereIn('u.user_id', $cohortUserIds)
            ->select('u.user_id as ispring_user_id', 'u.fields', 'd.name as department_name')
            ->get();

        $existingIspringIds = User::whereNotNull('ispring_user_id')
            ->pluck('ispring_user_id')
            ->toArray();

        $learners = [];
        foreach ($ispringUsers as $u) {
            $fields   = $u->fields ? json_decode($u->fields, true) : [];
            $fieldMap = $this->parseFieldMap($fields);

            $name = trim(($fieldMap['FIRST_NAME'] ?? '') . ' ' . ($fieldMap['LAST_NAME'] ?? ''))
                ?: $u->ispring_user_id;

            $learners[] = [
                'ispring_user_id' => $u->ispring_user_id,
                'name'            => $name,
                'department'      => $u->department_name ?? '—',
                'has_account'     => in_array($u->ispring_user_id, $existingIspringIds),
            ];
        }

        usort($learners, fn($a, $b) => strcmp($a['name'], $b['name']));

        return response()->json(['learners' => $learners]);
    }

    /* =========================================================
     |  BULK INVITE — create accounts for selected iSpring users
     | ========================================================= */

    public function bulkInvite(Request $request)
    {
        $request->validate([
            'ispring_user_ids'   => 'required|array|min:1',
            'ispring_user_ids.*' => 'required|string',
            'send_email'         => 'nullable|boolean',
        ]);

        $ispringUserIds = $request->input('ispring_user_ids');
        $sendEmail      = $request->boolean('send_email');

        $ispringUsers = DB::table('users_ispring as u')
            ->leftJoin('departments as d', 'u.department_id', '=', 'd.department_id')
            ->whereIn('u.user_id', $ispringUserIds)
            ->select('u.user_id as ispring_user_id', 'u.fields', 'u.department_id')
            ->get()
            ->keyBy('ispring_user_id');

        $existingIspringIds = User::whereIn('ispring_user_id', $ispringUserIds)
            ->pluck('ispring_user_id')
            ->toArray();

        $created      = [];
        $skippedCount = 0;

        foreach ($ispringUserIds as $ispringUserId) {
            if (in_array($ispringUserId, $existingIspringIds)) {
                $skippedCount++;
                continue;
            }

            $u = $ispringUsers->get($ispringUserId);
            if (!$u) { $skippedCount++; continue; }

            $fields   = $u->fields ? json_decode($u->fields, true) : [];
            $fieldMap = $this->parseFieldMap($fields);

            $name     = trim(($fieldMap['FIRST_NAME'] ?? '') . ' ' . ($fieldMap['LAST_NAME'] ?? ''))
                ?: $ispringUserId;
            $username = $this->generateUsername($name);
            $password = $this->generatePassword();

            User::create([
                'user_id'              => $username,
                'name'                 => $name,
                'email'                => null,
                'department_id'        => $u->department_id ?: null,
                'role'                 => 'L',
                'password'             => Hash::make($password),
                'must_change_password' => true,
                'ispring_user_id'      => $ispringUserId,
            ]);

            $created[] = ['name' => $name, 'username' => $username, 'password' => $password];
        }

        return response()->json([
            'success'       => true,
            'created_count' => count($created),
            'skipped_count' => $skippedCount,
            'credentials'   => $created,
        ]);
    }

    /* =========================================================
     |  SYNC EMAILS — fetch emails from iSpring and store them
     | ========================================================= */

    public function syncEmailsFromIspring()
    {
        $usersToSync = User::whereNotNull('ispring_user_id')
            ->whereNull('email')
            ->get();

        if ($usersToSync->isEmpty()) {
            return response()->json([
                'success' => true,
                'synced'  => 0,
                'message' => 'All linked learners already have an email address.',
            ]);
        }

        $ispringRecords = DB::table('users_ispring')
            ->whereIn('user_id', $usersToSync->pluck('ispring_user_id')->toArray())
            ->select('user_id', 'fields')
            ->get()
            ->keyBy('user_id');

        $synced  = 0;
        $noEmail = 0;

        foreach ($usersToSync as $user) {
            $ir = $ispringRecords->get($user->ispring_user_id);
            if (!$ir) { $noEmail++; continue; }

            $fields   = $ir->fields ? json_decode($ir->fields, true) : [];
            $fieldMap = $this->parseFieldMap($fields);

            $email = $fieldMap['EMAIL'] ?? $fieldMap['email'] ?? $fieldMap['Email'] ?? $fieldMap['LOGIN'] ?? null;

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user->update(['email' => $email]);
                $synced++;
            } else {
                $noEmail++;
            }
        }

        return response()->json([
            'success'  => true,
            'synced'   => $synced,
            'no_email' => $noEmail,
            'message'  => "{$synced} email(s) populated from iSpring. {$noEmail} learner(s) had no email in iSpring.",
        ]);
    }

    /* =========================================================
     |  PRIVATE HELPERS
     | ========================================================= */

    private function parseFieldMap(array $fields): array
    {
        $map = [];
        foreach ($fields['field'] ?? [] as $f) {
            if (is_array($f) && isset($f['name'])) {
                $map[$f['name']] = is_array($f['value']) ? '' : (string) ($f['value'] ?? '');
            }
        }
        return $map;
    }

    private function dispatchInvitationEmail(
        string $toEmail,
        string $recipientName,
        string $username,
        string $plainPassword,
        string $role,
    ): array {
        try {
            $mailable = $role === 'PC'
                ? new InvitationPC($recipientName, $username, $plainPassword)
                : new InvitationLearner($recipientName, $username, $plainPassword);

            Mail::to($toEmail)->send($mailable);
            return [true, null];
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];
        }
    }

    private function generateUsername(string $name): string
    {
        // Use only the first name for a shorter, cleaner username
        $first    = preg_split('/\s+/', trim($name))[0] ?? $name;
        $base     = preg_replace('/[^a-zA-Z0-9]/', '', $first);
        $username = $base;

        $suffix = 1;
        while (User::where('user_id', $username)->exists()) {
            $username = $base . $suffix++;
        }

        return $username;
    }

    private function generatePassword(int $length = 10): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $special = '@#$!';

        $password  = $upper[random_int(0, strlen($upper) - 1)];
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        $all = $upper . $lower . $digits . $special;
        while (strlen($password) < $length) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }
}
