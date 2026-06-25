<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SyncAll extends Command
{
    protected $signature   = 'sync:all';
    protected $description = 'Run all iSpring sync commands in parallel waves and track progress';

    private const STATUS_PATH = 'sync_status.json';

    // Wave 1: all independent — run in parallel
    private array $wave1 = [
        'users:sync',
        'departments:sync',
        'group:sync',
        'enrollment:sync',
        'modules:sync',
        'content:sync',
    ];

    // Wave 2: depends on wave 1 results — run in parallel with each other
    private array $wave2 = [
        'learner:sync-results',  // needs users_ispring (from users:sync)
        'map:course-modules',    // needs modules table (from modules:sync)
    ];

    public function handle(): int
    {
        $total = count($this->wave1) + count($this->wave2);

        $this->writeStatus([
            'status'       => 'running',
            'started_at'   => now()->toIso8601String(),
            'completed_at' => null,
            'step'         => 'Initialising sync...',
            'steps_done'   => 0,
            'steps_total'  => $total,
            'error'        => null,
        ]);

        $this->info('Wave 1: ' . implode(', ', $this->wave1));
        $this->updateStatus(['step' => 'Wave 1: syncing users, departments, groups, enrollments, modules, content...']);
        $this->runWave($this->wave1);

        $this->updateStatus([
            'step'       => 'Wave 2: syncing learner results and mapping course modules...',
            'steps_done' => count($this->wave1),
        ]);
        $this->info('Wave 2: ' . implode(', ', $this->wave2));
        $this->runWave($this->wave2);

        $this->updateStatus([
            'status'       => 'done',
            'completed_at' => now()->toIso8601String(),
            'step'         => 'Sync complete.',
            'steps_done'   => $total,
        ]);

        $this->info('sync:all complete.');
        return Command::SUCCESS;
    }

    private function runWave(array $cmds): void
    {
        $processes = [];

        foreach ($cmds as $cmd) {
            $p = new Process([PHP_BINARY, base_path('artisan'), ...explode(' ', $cmd)]);
            $p->setTimeout(600);
            $p->start();
            $processes[$cmd] = $p;
            $this->line("  started: {$cmd}");
        }

        foreach ($processes as $cmd => $p) {
            $p->wait();
            foreach (explode("\n", trim($p->getOutput())) as $line) {
                if ($line !== '') $this->line("  [{$cmd}] {$line}");
            }
            if (!$p->isSuccessful() && $p->getErrorOutput()) {
                $this->warn("  [{$cmd}] ERROR: " . trim($p->getErrorOutput()));
            }
        }
    }

    private function writeStatus(array $data): void
    {
        file_put_contents(storage_path('app/' . self::STATUS_PATH), json_encode($data), LOCK_EX);
    }

    private function updateStatus(array $partial): void
    {
        $path = storage_path('app/' . self::STATUS_PATH);
        $existing = file_exists($path)
            ? (json_decode(file_get_contents($path), true) ?? [])
            : [];
        file_put_contents($path, json_encode(array_merge($existing, $partial)), LOCK_EX);
    }
}
