<?php

namespace App\Console\Commands;

use App\Console\Concerns\FetchesIspringToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncLearnerResults extends Command
{
    use FetchesIspringToken;

    protected $signature = 'learner:sync-results
                                {--from=0 : Resume from this batch index (0-based)}
                                {--test   : Run only the first batch (50 users) to verify}
                                {--users= : Comma-separated user_ids to sync (skips DB lookup)}
                                {--chunk=50 : Users per API request}';

    protected $description = 'Sync learner module results from iSpring API (batch endpoint)';

    private const DB_CHUNK    = 500;
    private const CONCURRENCY = 10; // batches sent simultaneously via Http::pool()

    public function handle()
    {
        $token = $this->fetchToken();
        if (!$token) {
            $this->error('Failed to get access token.');
            return Command::FAILURE;
        }

        // ── Learner user IDs ──────────────────────────────────────────
        $usersOption = $this->option('users');
        if ($usersOption) {
            $userIds = array_values(array_filter(array_map('trim', explode(',', $usersOption))));
            $this->info(count($userIds) . ' user_id(s) from --users option.');
        } else {
            $userIds = DB::table('users_ispring')
                ->where('role', 'learner')
                ->pluck('user_id')
                ->values()
                ->all();
        }

        $totalUsers = count($userIds);
        if ($totalUsers === 0) {
            $this->warn('No learners found in users_ispring.');
            return Command::SUCCESS;
        }

        $chunkSize    = max(1, (int) $this->option('chunk'));
        $batches      = array_chunk($userIds, $chunkSize);
        $totalBatches = count($batches);

        if ($this->option('test')) {
            $batches      = array_slice($batches, 0, 1);
            $totalBatches = count($batches);
            $this->info("TEST MODE — first batch ({$chunkSize} users).");
        }

        $fromBatch = (int) $this->option('from');
        $batches   = array_slice($batches, $fromBatch);
        $this->info("{$totalUsers} learner(s) in {$totalBatches} batch(es) of {$chunkSize}. Starting from batch {$fromBatch}.");

        // ── Concurrent batch sync ─────────────────────────────────────
        $baseUrl     = rtrim(config('services.cfip.base_url'), '/');
        $url         = $baseUrl . '/learners/modules/results';
        $totalSaved  = 0;
        $batchesDone = 0;

        foreach (array_chunk($batches, self::CONCURRENCY) as $groupIdx => $group) {
            // Refresh token every 10 groups (~500 users' worth of batches)
            if ($groupIdx > 0 && $groupIdx % 10 === 0) {
                Cache::forget('ispring_token');
                $refreshed = $this->fetchToken();
                if ($refreshed) {
                    $token = $refreshed;
                    $this->line('  [token refreshed]');
                }
            }

            $responses = Http::pool(function ($pool) use ($group, $token, $url) {
                return array_map(function ($batchIds) use ($pool, $token, $url) {
                    $query = implode('&', array_map(fn ($id) => 'userIds[]=' . urlencode($id), $batchIds));
                    return $pool->withToken($token)->timeout(120)->get("{$url}?{$query}");
                }, $group);
            });

            foreach ($responses as $i => $response) {
                $batchIds = $group[$i];
                $batchNum = $fromBatch + $groupIdx * self::CONCURRENCY + $i + 1;

                if ($response instanceof \Throwable) {
                    $this->warn("  [batch {$batchNum}/{$totalBatches}] connection error — skipping");
                    continue;
                }

                if ($response->status() === 401) {
                    Cache::forget('ispring_token');
                    $token = $this->fetchToken() ?? $token;
                    $query = implode('&', array_map(fn ($id) => 'userIds[]=' . urlencode($id), $batchIds));
                    $response = Http::withToken($token)->timeout(120)->get("{$url}?{$query}");
                }

                // 400 = one or more invalid user IDs — retry individually to skip bad ones
                if ($response->status() === 400 && count($batchIds) > 1) {
                    $this->warn("  [batch {$batchNum}/{$totalBatches}] HTTP 400 — retrying individually to find invalid IDs");
                    $saved = 0;
                    foreach ($batchIds as $userId) {
                        $q = 'userIds[]=' . urlencode($userId);
                        $r = Http::withToken($token)->timeout(30)->get("{$url}?{$q}");
                        if ($r->status() === 400) {
                            $this->warn("    skip invalid user: {$userId}");
                            continue;
                        }
                        if ($r->ok()) {
                            $saved += $this->saveBody($r->body());
                        }
                    }
                    $this->line("  [batch {$batchNum}/{$totalBatches}] individual fallback → {$saved} row(s)");
                    $totalSaved += $saved;
                    $batchesDone++;
                    continue;
                }

                if (!$response->ok()) {
                    $this->warn("  [batch {$batchNum}/{$totalBatches}] HTTP {$response->status()} — skipping");
                    continue;
                }

                $saved = $this->saveBody($response->body());
                $totalSaved += $saved;
                $batchesDone++;
                $this->line(sprintf(
                    '  [batch %d/%d] %d users → %d row(s)',
                    $batchNum, $totalBatches, count($batchIds), $saved
                ));
            }
        }

        $this->newLine();
        $this->info('Done.');
        $this->info('  Batches processed : ' . $batchesDone);
        $this->info('  Rows saved        : ' . number_format($totalSaved));
        $this->info('  DB rows total     : ' . number_format(DB::table('learner_module_results')->count()));
        $this->info('  Users with data   : ' . DB::table('learner_module_results')->distinct('user_id')->count('user_id'));

        return Command::SUCCESS;
    }

    private function saveBody(string $body): int
    {
        $body = preg_replace('/<\?xml[^?]*\?>/', '', $body);
        $xml  = @simplexml_load_string($body);
        if (!$xml) return 0;

        $items = $this->extractItems($xml);
        if (empty($items)) return 0;

        $rows = [];
        $now  = now();

        foreach ($items as $item) {
            $row = json_decode(json_encode($item), true);

            $courseItemId = $this->val($row['courseItemId'] ?? null)
                         ?? $this->val($row['courseId']     ?? null);

            if (!$courseItemId) continue;

            $userId = $this->val($row['userId'] ?? null);
            if (!$userId) continue;

            $enrollmentId = $this->val($row['enrollmentId'] ?? null) ?? '';

            $rows[] = [
                'course_item_id'    => $courseItemId,
                'user_id'           => $userId,
                'course_id'         => $this->val($row['courseId']           ?? null),
                'module_id'         => $this->val($row['moduleId']            ?? null),
                'module_title'      => $this->val($row['moduleTitle']         ?? null)
                                    ?? $this->val($row['courseTitle']         ?? null),
                'enrollment_id'     => $enrollmentId,
                'access_date'       => $this->date($row['accessDate']         ?? null),
                'completion_date'   => $this->date($row['completionDate']     ?? null),
                'time_spent'        => (int) ($this->val($row['timeSpent']     ?? null) ?? 0),
                'completion_status' => strtolower(trim($this->val($row['completionStatus'] ?? '') ?? '')),
                'progress'          => (int) ($this->val($row['progress']      ?? null) ?? 0),
                'is_overdue'        => $this->val($row['isOverdue']            ?? '0') == '1',
                'views_count'       => (int) ($this->val($row['viewsCount']    ?? null) ?? 0),
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        if (empty($rows)) return 0;

        foreach (array_chunk($rows, self::DB_CHUNK) as $chunk) {
            DB::table('learner_module_results')->upsert(
                $chunk,
                ['course_item_id', 'user_id', 'enrollment_id'],
                [
                    'course_id', 'module_id', 'module_title',
                    'access_date', 'completion_date',
                    'time_spent', 'completion_status', 'progress',
                    'is_overdue', 'views_count', 'updated_at',
                ]
            );
        }

        return count($rows);
    }

    private function extractItems(\SimpleXMLElement $xml): array
    {
        if (isset($xml->results->result) && count($xml->results->result) > 0) {
            return iterator_to_array($xml->results->result, false);
        }
        if (isset($xml->result) && count($xml->result) > 0) {
            return iterator_to_array($xml->result, false);
        }
        $byXpath = $xml->xpath('//*[local-name()="result"]') ?: [];
        if (!empty($byXpath)) return $byXpath;

        foreach ($xml->children() as $child) {
            if (count($child) > 0) {
                return iterator_to_array($child->children(), false);
            }
        }
        return [];
    }

    private function val($value): ?string
    {
        if ($value === null) return null;
        if ($value instanceof \SimpleXMLElement) $value = (string) $value;
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if ($k === '@attributes') continue;
                if ($v !== null && $v !== '') return is_scalar($v) ? (string) $v : json_encode($v);
            }
            return null;
        }
        return is_scalar($value) ? (string) $value : null;
    }

    private function date($raw): ?string
    {
        $s = $this->val($raw);
        if (!$s) return null;
        try {
            return Carbon::parse($s)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
