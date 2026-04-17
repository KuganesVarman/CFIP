<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Enrollment;

class SyncEnrollments extends Command
{
    protected $signature = 'enrollment:sync';
    protected $description = 'Sync enrollments from iSpring API';

    public function handle()
    {
        $apiUrl = config('services.cfip.base_url') . '/enrollments';
        $tokenResp = Http::asForm()->post(config('services.cfip.token_url'), [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.cfip.client_id'),
            'client_secret' => config('services.cfip.client_secret'),
        ]);
        $token = $tokenResp->json('access_token');
        if (!$token) {
            $this->error('Failed to get token!');
            return;
        }

        $response = Http::timeout(120)
            ->withToken($token)
            ->get($apiUrl);
        $body = $response->body();
$body = preg_replace('/<\?xml.*?\?>/', '', $body); // Remove XML declaration
$xml = simplexml_load_string($body);

if ($xml && isset($xml->enrollments->enrollment)) {
    foreach ($xml->enrollments->enrollment as $item) {
        $data = json_decode(json_encode($item), true);
        \App\Models\Enrollment::updateOrCreate(
            ['enrollment_id' => $data['enrollmentId'] ?? null],
            [
                'course_id' => $data['courseId'] ?? null,
                'learner_id' => $data['learnerId'] ?? null,
                'access_date' => $data['accessDate'] ?? null,
                'enrollment_type_group' => isset($data['enrollmentTypeGroup']) ? (int)$data['enrollmentTypeGroup'] : null,
                'should_lock_after_due_date' => isset($data['shouldLockAfterDueDate']) ? (bool)$data['shouldLockAfterDueDate'] : false,
            ]
        );
    }
    $this->info('Enrollments synced successfully.');
} else {
    $this->error('No enrollments found in API response.');
    dump($body); // For further debugging
}

    }
}
