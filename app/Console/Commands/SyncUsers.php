<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\IspringUser;

class SyncUsers extends Command
{
    protected $signature = 'users:sync';
    protected $description = 'Sync users from iSpring API';

    public function handle()
    {
        $apiUrl = rtrim(config('services.cfip.base_url'), '/') . '/users';

        // 1. Get Token
        $tokenResp = Http::asForm()->post(config('services.cfip.token_url'), [
            'grant_type'    => 'client_credentials',
            'client_id'     => config('services.cfip.client_id'),
            'client_secret' => config('services.cfip.client_secret'),
        ]);

        $token = $tokenResp->json('access_token');

        if (!$token) {
            $this->error('❌ Failed to get access token!');
            return Command::FAILURE;
        }

        // 2. Call API
        $response = Http::timeout(60)->withToken($token)->get($apiUrl);

        $body = $response->body();
        $body = preg_replace('/<\?xml.*?\?>/', '', $body);

        $xml = @simplexml_load_string($body);

        

        if (!$xml) {
            $this->error('❌ Failed to parse XML from API!');
            return Command::FAILURE;
        }

        // Helper for scalar conversion
        $scalar = function ($value, $default = '') {
            if (is_array($value)) {
                $flat = array_values(array_filter($value, fn($v) => $v !== null && $v !== ''));
                return $flat[0] ?? $default;
            }
            if ($value instanceof \SimpleXMLElement) return (string)$value;
            return $value ?? $default;
        };

        // Determine correct XML structure
        $items = null;

        // Correct XML structure for iSpring Users API
if (isset($xml->userProfiles->userProfile)) {
    $items = $xml->userProfiles->userProfile;
} elseif (isset($xml->userProfile)) {
    $items = $xml->userProfile;
} else {
    $this->error("❌ Could not find <userProfile> nodes in XML!");
    return Command::FAILURE;
}


        $count = 0;

        foreach ($items as $item) {

            $data = json_decode(json_encode($item), true);

            $userId       = $scalar($data['userId'] ?? null);
            $role         = $scalar($data['role'] ?? null);
            $roleId       = $scalar($data['roleId'] ?? null);
            $departmentId = $scalar($data['departmentId'] ?? null);
            $status       = $scalar($data['status'] ?? null);

            // JSON → arrays
            $fields     = $data['fields']      ?? [];
            $userRoles  = $data['userRoles']   ?? [];
            $groups     = $data['groups']      ?? [];

            $parseDate = function ($value) {
                $value = trim($value ?? "");

                if ($value === "" || $value === null) {
                 return null;
                }

                 try {
                      return \Carbon\Carbon::parse($value)->format('Y-m-d');
                 } catch (\Exception $e) {
                     return null; // fallback on bad date formats
                  }
            };

             $addedDate      = $parseDate($data['addedDate'] ?? null);
            $lastLoginDate  = $parseDate($data['lastLoginDate'] ?? null);

            $subordinationType    = $scalar($data['subordination']['subordinationType'] ?? null);
            $coSubordinationType  = $scalar($data['coSubordination']['subordinationType'] ?? null);

            if (!$userId) {
                $this->warn('⚠️ Skipped user without userId');
                continue;
            }

            IspringUser::updateOrCreate(
                ['user_id' => $userId],
                [
                    'role'                 => $role,
                    'role_id'              => $roleId,
                    'department_id'        => $departmentId,
                    'status'               => (int) $status,
                    'fields'               => $fields,
                    'user_roles'           => $userRoles,
                    'groups'               => $groups,
                    'added_date'           => $addedDate,
                    'last_login_date'      => $lastLoginDate,
                    'subordination_type'   => $subordinationType,
                    'co_subordination_type'=> $coSubordinationType,
                ]
            );

            $count++;
        }

        $this->info("✅ Users synced successfully. Total: {$count}");
        return Command::SUCCESS;
    }
}
