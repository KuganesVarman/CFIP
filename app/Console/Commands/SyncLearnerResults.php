<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\LearnerModuleResult;

class SyncLearnerResults extends Command
{
    protected $signature = 'learner:sync-results';
    protected $description = 'Fetch learner module results from iSpring API';

    public function handle()
    {
        $this->info("Fetching learner module results...");

        // 1. Get token
        $tokenResp = Http::asForm()->post(config('services.cfip.token_url'), [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.cfip.client_id'),
            'client_secret' => config('services.cfip.client_secret'),
        ]);

        $token = $tokenResp->json('access_token');
        if (!$token) {
            $this->error("Failed to get token!");
            return;
        }

        // 2. Fetch the FULL result list (10,000 rows max)
        $url = config('services.cfip.base_url') . '/learners/modules/results';

        $response = Http::timeout(300)->withToken($token)->get($url);
        $xml = simplexml_load_string($response->body());

        if (!$xml || !isset($xml->results->result)) {
            $this->error("API returned no results.");
            return;
        }

        $results = $xml->results->result;
        $count = count($results);

        $this->info("API returned {$count} results. Saving...");

        $saved = 0;

        foreach ($results as $item) {
            $row = json_decode(json_encode($item), true);

            LearnerModuleResult::updateOrCreate(
    ['course_item_id' => $row['courseItemId'] ?? null],
    [
        'user_id'         => $row['userId'] ?? null,
        'course_id'       => $row['courseId'] ?? null,
        'module_id'       => $row['moduleId'] ?? null,
        'module_title'    => $row['moduleTitle'] ?? null,
        'enrollment_id'   => $row['enrollmentId'] ?? null,
        'access_date'     => $row['accessDate'] ?? null,
        'completion_date' => $row['completionDate'] ?? null,
        'time_spent'      => (int)($row['timeSpent'] ?? 0),
        'completion_status' => $row['completionStatus'] ?? null,
        'progress'        => (int)($row['progress'] ?? 0),
        'is_overdue'      => ($row['isOverdue'] ?? '0') == '1',
        'views_count'     => (int)($row['viewsCount'] ?? 0),
    ]
);


            $saved++;
        }

        $this->info("Saved {$saved} learner module results.");
    }
}
