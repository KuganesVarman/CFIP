<?php

namespace App\Console\Commands;

use App\Console\Concerns\FetchesIspringToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\IspringUser;

class SyncUsers extends Command
{
    use FetchesIspringToken;

    protected $signature   = 'users:sync';
    protected $description = 'Sync users from iSpring API';

    public function handle()
    {
        $apiUrl = rtrim(config('services.cfip.base_url'), '/') . '/users';

        $token = $this->fetchToken();
        if (!$token) {
            $this->error('❌ Failed to get access token!');
            return Command::FAILURE;
        }

        // ── 2. Call API ────────────────────────────────────────
        $response = Http::timeout(60)->withToken($token)->get($apiUrl);
        $body     = preg_replace('/<\?xml.*?\?>/', '', $response->body());
        $xml      = @simplexml_load_string($body);

        if (!$xml) {
            $this->error('❌ Failed to parse XML from API!');
            return Command::FAILURE;
        }

        // ── Helper: normalize a value to a plain string ────────
        $scalar = function ($value, $default = '') {
            if (is_array($value)) {
                $flat = array_values(array_filter($value, fn($v) => $v !== null && $v !== ''));
                return $flat[0] ?? $default;
            }
            if ($value instanceof \SimpleXMLElement) {
                return (string) $value;
            }
            return $value ?? $default;
        };

        // ── Helper: parse a date safely ────────────────────────
        $parseDate = function ($value) {
            $value = trim($value ?? '');
            if ($value === '') return null;
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        };

        // ── 3. Find user nodes ─────────────────────────────────
        if (isset($xml->userProfiles->userProfile)) {
            $items = $xml->userProfiles->userProfile;
        } elseif (isset($xml->userProfile)) {
            $items = $xml->userProfile;
        } else {
            $this->error('❌ Could not find <userProfile> nodes in XML!');
            return Command::FAILURE;
        }

        $count = 0;

        foreach ($items as $item) {

            $data = json_decode(json_encode($item), true);

            $userId       = $scalar($data['userId']       ?? null);
            $role         = $scalar($data['role']         ?? null);
            $roleId       = $scalar($data['roleId']       ?? null);
            $departmentId = $scalar($data['departmentId'] ?? null);
            $status       = $scalar($data['status']       ?? null);

            $fields    = $data['fields']    ?? [];
            $userRoles = $data['userRoles'] ?? [];

            $addedDate     = $parseDate($data['addedDate']     ?? null);
            $lastLoginDate = $parseDate($data['lastLoginDate'] ?? null);

            $subordinationType   = $scalar($data['subordination']['subordinationType']   ?? null);
            $coSubordinationType = $scalar($data['coSubordination']['subordinationType'] ?? null);

            if (!$userId) {
                $this->warn('⚠️ Skipped user without userId');
                continue;
            }

            // ── Extract group IDs from the groups node ─────────
            // The API returns something like:
            // <groups><groupId>uuid-1</groupId><groupId>uuid-2</groupId></groups>
            // After json_decode it becomes either:
            //   ['groupId' => 'uuid']          — single group
            //   ['groupId' => ['uuid1','uuid2']]— multiple groups
            //   []                              — no groups
            $groupIds = [];

            $rawGroups = $data['groups'] ?? [];

            if (!empty($rawGroups)) {
                $raw = $rawGroups['id'] ?? $rawGroups['groupId'] ?? [];

                if (is_string($raw) && $raw !== '') {
                    // single group returned as plain string
                    $groupIds = [$raw];
                } elseif (is_array($raw)) {
                    // multiple groups or already an array
                    $groupIds = array_values(array_filter($raw, fn($v) => is_string($v) && $v !== ''));
                }
            }

            // ── Upsert the user record ─────────────────────────
            IspringUser::updateOrCreate(
                ['user_id' => $userId],
                [
                    'role'                  => $role,
                    'role_id'               => $roleId,
                    'department_id'         => $departmentId,
                    'status'                => (int) $status,
                    'fields'                => $fields,
                    'user_roles'            => $userRoles,
                    'groups'                => $groupIds,   // keep JSON copy on the user row too
                    'added_date'            => $addedDate,
                    'last_login_date'       => $lastLoginDate,
                    'subordination_type'    => $subordinationType,
                    'co_subordination_type' => $coSubordinationType,
                ]
            );

            // ── Sync pivot table (user_group) ──────────────────
            // Delete old memberships for this user then re-insert,
            // so removed group memberships are cleaned up too.
            DB::table('user_group')->where('user_id', $userId)->delete();

            foreach ($groupIds as $groupId) {
                DB::table('user_group')->insertOrIgnore([
                    'user_id'    => $userId,
                    'group_id'   => $groupId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $count++;
        }

        $this->info("✅ Users synced successfully. Total: {$count}");
        return Command::SUCCESS;
    }
}