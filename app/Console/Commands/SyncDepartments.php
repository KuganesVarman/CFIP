<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Department;

class SyncDepartments extends Command
{
    protected $signature = 'departments:sync';
    protected $description = 'Sync departments from iSpring API';

    public function handle()
    {
        $apiUrl = rtrim(config('services.cfip.base_url'), '/') . '/departments';

        // STEP 1 — Get Token
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

        // STEP 2 — Call API
        $response = Http::timeout(60)->withToken($token)->get($apiUrl);

        $body = $response->body();
        $body = preg_replace('/<\?xml.*?\?>/', '', $body);

        $xml = @simplexml_load_string($body);

        if (!$xml) {
            $this->error('❌ Failed to parse XML from departments API!');
            return Command::FAILURE;
        }

        // Helper — Normalize values like your previous commands
        $scalar = function ($value, $default = '') {
            if (is_array($value)) {
                $flat = array_values(array_filter($value, fn($v) => $v !== null && $v !== ''));
                return isset($flat[0]) ? (string)$flat[0] : $default;
            }
            if ($value instanceof \SimpleXMLElement) {
                return (string)$value;
            }
            return $value !== null ? (string)$value : $default;
        };

        // Determine structure
        $items = null;

        if (isset($xml->departments->department)) {
            $items = $xml->departments->department;
        } elseif (isset($xml->department)) {
            $items = $xml->department;
        } else {
            $items = $xml;
        }

        $count = 0;

        foreach ($items as $item) {
            $data = json_decode(json_encode($item), true);

            $departmentId   = $scalar($data['departmentId'] ?? null);
            $name           = $scalar($data['name'] ?? 'Unknown Department');
            $parentId       = $scalar($data['parentDepartmentId'] ?? null);
            $code           = $scalar($data['code'] ?? null);

            // nested node: subordination.subordinationType
            $subordinationType = $scalar($data['subordination']['subordinationType'] ?? null);
            $coSubordinationType = $scalar($data['coSubordination']['subordinationType'] ?? null);

            if (!$departmentId) {
                $this->warn("⚠️ Skipped department with missing departmentId.");
                continue;
            }

            Department::updateOrCreate(
                ['department_id' => $departmentId],
                [
                    'name'                   => $name,
                    'parent_department_id'   => $parentId,
                    'code'                   => $code,
                    'subordination_type'     => $subordinationType,
                    'co_subordination_type'  => $coSubordinationType,
                ]
            );

            $count++;
        }

        $this->info("✅ Departments synced successfully. Total: {$count}");
        return Command::SUCCESS;
    }
}
