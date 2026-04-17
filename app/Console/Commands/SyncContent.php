<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Content;

class SyncContent extends Command
{
    protected $signature = 'content:sync';
    protected $description = 'Sync content (courses/lessons) from iSpring API';

    public function handle()
    {
        $apiUrl = config('services.cfip.base_url') . '/content';

        // 🔹 Step 1: Get Access Token
        $tokenResp = Http::asForm()->post(config('services.cfip.token_url'), [
            'grant_type'    => 'client_credentials',
            'client_id'     => config('services.cfip.client_id'),
            'client_secret' => config('services.cfip.client_secret'),
        ]);

        $token = $tokenResp->json('access_token');
        if (!$token) {
            $this->error('❌ Failed to get token!');
            return Command::FAILURE;
        }

        // 🔹 Step 2: Fetch XML data
        $response = Http::timeout(60)->withToken($token)->get($apiUrl);
        $body = $response->body();
        $body = preg_replace('/<\?xml.*?\?>/', '', $body); // Remove XML declaration
        $xml = @simplexml_load_string($body);

        if (!$xml || !isset($xml->contentItem)) {
            $this->error('⚠️ No content found in XML response!');
            return Command::FAILURE;
        }

        // --- Helper functions ---
        $scalar = function ($value, $default = null) {
            // Ensures only strings get saved to DB
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
            return is_scalar($value) || $value === null
                ? ($value ?? $default)
                : json_encode($value);
        };

        $arr = function ($value): array {
            // Always return a flat array of strings
            if ($value === null || $value === '') return [];
            if ($value instanceof \SimpleXMLElement) $value = (string) $value;
            $out = is_array($value) ? $value : [$value];
            $flat = [];
            foreach ($out as $v) {
                if ($v instanceof \SimpleXMLElement) $v = (string) $v;
                if (is_array($v)) {
                    foreach ($v as $vv) {
                        if ($vv !== null && $vv !== '') $flat[] = (string) $vv;
                    }
                } elseif ($v !== null && $v !== '') {
                    $flat[] = (string) $v;
                }
            }
            return array_values(array_unique($flat));
        };
        // -------------------------

        $count = 0;

        // 🔹 Step 3: Parse and store
        foreach ($xml->contentItem as $item) {
            $data = json_decode(json_encode($item), true);

            $contentItemId   = $scalar($data['contentItemId'] ?? null);
            $title = $scalar($data['title'] ?? '') ?: 'Untitled Content';
            $subtitle        = $scalar($data['subtitle'] ?? null);
            $description     = $scalar($data['description'] ?? null);
            $userId          = $scalar($data['userId'] ?? null);
            $addedDate       = $scalar($data['addedDate'] ?? null);
            $type            = $scalar($data['type'] ?? null);
            $contentItemType = $scalar($data['contentItemType'] ?? null);

            // View URL (make sure single string)
            $viewUrls = $arr($data['viewUrl'] ?? null);
            $viewUrl = $viewUrls[0] ?? null;

            // Course fields (array)
            $courseFields = $arr($data['courseFields'] ?? null);

            if (!$contentItemId) {
                $this->warn('⚠️ Skipped item without contentItemId.');
                continue;
            }

            Content::updateOrCreate(
                ['content_item_id' => $contentItemId],
                [
                    'title'              => $title,
                    'subtitle'           => $subtitle,
                    'description'        => $description,
                    'user_id'            => $userId,
                    'added_date'         => $addedDate,
                    'view_url'           => $viewUrl,
                    'type'               => $type,
                    'content_item_type'  => $contentItemType,
                    'course_fields'      => $courseFields, // ✅ model will JSON encode
                ]
            );

            $count++;
        }

        $this->info("✅ Content synced successfully. Total: {$count}");
        return Command::SUCCESS;
    }
}
