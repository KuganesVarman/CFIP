<?php

// app/Console/Commands/SyncModules.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Module;

class SyncModules extends Command
{
    protected $signature = 'modules:sync';
    protected $description = 'Sync course modules from iSpring API';

    public function handle()
    {
        $apiUrl = config('services.cfip.base_url') . '/courses/modules';

        // 1) Token
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

        // 2) Fetch
        $response = Http::timeout(60)->withToken($token)->get($apiUrl);
        $body = $response->body();
        $body = preg_replace('/<\?xml.*?\?>/', '', $body); // strip XML declaration

        $xml = @simplexml_load_string($body);
        if (!$xml || !isset($xml->modules->module)) {
            $this->error('No modules found in XML response!');
            // optional: $this->line($body);
            return Command::FAILURE;
        }

        // --- helpers to normalize XML values ---
        $scalar = function ($value, $default = null) {
            // if array, take first non-empty element
            if (is_array($value)) {
                foreach ($value as $v) {
                    if ($v !== null && $v !== '') {
                        return is_scalar($v) ? (string) $v : json_encode($v);
                    }
                }
                return $default;
            }
            if ($value instanceof \SimpleXMLElement) {
                $value = (string) $value;
            }
            if (is_scalar($value) || $value === null) {
                return $value === null ? $default : (string) $value;
            }
            // fallback: encode objects
            return json_encode($value);
        };

        $arr = function ($value): array {
            // make sure we end up with a flat array of strings
            if ($value instanceof \SimpleXMLElement) {
                $value = (string) $value;
            }
            if ($value === null || $value === '') {
                return [];
            }
            $out = is_array($value) ? $value : [$value];
            $flat = [];
            foreach ($out as $v) {
                if ($v instanceof \SimpleXMLElement) {
                    $v = (string) $v;
                }
                if (is_array($v)) {
                    // flatten nested arrays
                    foreach ($v as $vv) {
                        if ($vv !== null && $vv !== '') $flat[] = (string) $vv;
                    }
                } elseif ($v !== null && $v !== '') {
                    $flat[] = (string) $v;
                }
            }
            return array_values(array_unique($flat));
        };
        // ---------------------------------------

        // 3) Upsert modules
        $count = 0;
        foreach ($xml->modules->module as $item) {
            $data = json_decode(json_encode($item), true); // array form

            // Normalize every field
            $moduleId       = $scalar($data['moduleId']       ?? null);
            $contentItemId  = $scalar($data['contentItemId']  ?? null);
            $courseId       = $scalar($data['courseId']       ?? null);
            $title          = $scalar($data['title']          ?? '');
            $description    = $scalar($data['description']    ?? null);
            $authorId       = $scalar($data['authorId']       ?? null);
            $addedDate      = $scalar($data['addedDate']      ?? null);
            $viewUrls       = $arr($data['viewUrl']           ?? null); // array of strings

            if (!$moduleId) {
                // Skip bad records, or log them
                $this->warn('Skipped a module without moduleId.');
                continue;
            }

            Module::updateOrCreate(
                ['module_id' => $moduleId],
                [
                    'content_item_id' => $contentItemId,
                    'course_id'       => $courseId,
                    'title'           => $title,
                    'description'     => $description,
                    'author_id'       => $authorId,
                    'added_date'      => $addedDate,  // model cast handles datetime
                    'view_url'        => $viewUrls,   // ✅ pass array; model casts to JSON
                ]
            );

            $count++;
        }

        $this->info("Modules synced successfully. Upserted: {$count}");
        return Command::SUCCESS;
    }
}
