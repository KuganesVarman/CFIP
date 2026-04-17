<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\CourseModule;
use Carbon\Carbon;

class SyncCourseModules extends Command
{
    // you can pass the courseId as an argument, defaulting to your example ID
    protected $signature = 'course-modules:sync {courseId=9bb06490-37cd-11ef-9470-42cc767d5507}';
    protected $description = 'Sync modules for a specific course from iSpring API';

    public function handle()
    {
        $courseId = $this->argument('courseId');

        // e.g. https://confidential.ispringlearn.com/api/v2/course/{courseId}/modules
        $apiUrl = rtrim(config('services.cfip.base_url'), '/') . "/course/{$courseId}/modules";

        // 1) Get token (same as your other commands)
        $tokenResp = Http::asForm()->post(config('services.cfip.token_url'), [
            'grant_type'    => 'client_credentials',
            'client_id'     => config('services.cfip.client_id'),
            'client_secret' => config('services.cfip.client_secret'),
        ]);

        $token = $tokenResp->json('access_token');
        if (!$token) {
            $this->error('Failed to get token!');
            return Command::FAILURE;
        }

        // 2) Call the API
        $response = Http::timeout(60)->withToken($token)->get($apiUrl);

        if (!$response->ok()) {
            $this->error('Failed to fetch course modules from API.');
            $this->line($response->body());
            return Command::FAILURE;
        }

        $body = $response->body();

        // If this endpoint returns XML like your other ones, keep this:
        $body = preg_replace('/<\?xml.*?\?>/', '', $body);
        $xml = @simplexml_load_string($body);

        // If it returns JSON directly, you can instead do:
        // $items = $response->json();
        // and skip XML handling. For now we follow your previous style.

        if (!$xml) {
            $this->error('Failed to parse XML from course modules API!');
            // Debug raw response:
            // $this->line($body);
            return Command::FAILURE;
        }

        // ---- Helpers to normalize values (same idea as previous commands) ----
        $scalar = function ($value, $default = '') {
            if (is_array($value)) {
                $flat = array_values(array_filter(
                    $value,
                    fn($v) => $v !== null && $v !== ''
                ));
                return isset($flat[0]) ? (string)$flat[0] : $default;
            }
            if ($value instanceof \SimpleXMLElement) {
                return (string)$value;
            }
            return $value !== null ? (string)$value : $default;
        };
        // ----------------------------------------------------------------------

        $count = 0;

        /**
         * Now we need to know how the XML structure looks, but usually it will be
         * something like:
         * <response>
         *   <modules>
         *     <module> ...fields... </module>
         *   </modules>
         * </response>
         *
         * Adjust this path if your actual XML is different.
         */
        // Try some common shapes:
        $modulesNode = null;

        if (isset($xml->modules->module)) {
            $modulesNode = $xml->modules->module;
        } elseif (isset($xml->module)) {
            $modulesNode = $xml->module;
        } else {
            // last fallback: maybe the root itself is a list
            $modulesNode = $xml;
        }

        foreach ($modulesNode as $item) {
            $data = json_decode(json_encode($item), true);

            $moduleId         = $scalar($data['moduleId'] ?? null);
            $itemId           = $scalar($data['itemId'] ?? null);
            $courseIdValue    = $scalar($data['courseId'] ?? $courseId);
            $title            = $scalar($data['title'] ?? 'Untitled Module');
            $description      = $scalar($data['description'] ?? null);
            $authorId         = $scalar($data['authorId'] ?? null);
            $addedDateRaw     = $scalar($data['addedDate'] ?? null);
            $type             = $scalar($data['type'] ?? null);
            $viewUrl          = $scalar($data['viewUrl'] ?? null);
            $sequentialNumber = $scalar($data['sequentialNumber'] ?? null);

            if (!$moduleId) {
                $this->warn('Skipped a module without moduleId.');
                continue;
            }

            // Normalize added_date into "Y-m-d H:i:s" for DB
            $addedDate = null;
            if ($addedDateRaw) {
                try {
                    $addedDate = Carbon::parse($addedDateRaw)->toDateTimeString();
                } catch (\Exception $e) {
                    $this->warn("Invalid addedDate for module {$moduleId}: {$addedDateRaw}");
                }
            }

            CourseModule::updateOrCreate(
                ['module_id' => $moduleId],
                [
                    'item_id'           => $itemId,
                    'course_id'         => $courseIdValue,
                    'title'             => $title,
                    'description'       => $description,
                    'author_id'         => $authorId,
                    'added_date'        => $addedDate,
                    'type'              => $type,
                    'view_url'          => $viewUrl,
                    'sequential_number' => $sequentialNumber !== null ? (int)$sequentialNumber : null,
                ]
            );

            $count++;
        }

        $this->info("Course modules synced successfully. Total: {$count}");
        return Command::SUCCESS;
    }
}
