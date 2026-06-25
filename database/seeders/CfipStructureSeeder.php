<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CfipStructureSeeder extends Seeder
{
    public function run(): void
    {
        // ── Levels ─────────────────────────────────────────────────
        $levels = [
            ['name' => 'Entry',          'code' => 'entry',          'order' => 1],
            ['name' => 'Professional',   'code' => 'professional',   'order' => 2],
            ['name' => 'Specialization', 'code' => 'specialization', 'order' => 3],
        ];

        foreach ($levels as $level) {
            DB::table('levels')->updateOrInsert(
                ['code' => $level['code']],
                array_merge($level, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $entryId = DB::table('levels')->where('code', 'entry')->value('id');

        // ── Domains (Entry level only for now) ─────────────────────
        $domains = [
            ['level_id' => $entryId, 'name' => 'Foundation',                  'code' => 'foundation',       'order' => 1],
            ['level_id' => $entryId, 'name' => 'Legal & Ethics',               'code' => 'legal_ethics',     'order' => 2],
            ['level_id' => $entryId, 'name' => 'Crime Investigation',          'code' => 'crime_inv',        'order' => 3],
            ['level_id' => $entryId, 'name' => 'Soft Skill Competencies',      'code' => 'soft_skills',      'order' => 4],
            ['level_id' => $entryId, 'name' => 'Investigation Techniques',     'code' => 'inv_techniques',   'order' => 5],
        ];

        foreach ($domains as $domain) {
            DB::table('domains')->updateOrInsert(
                ['code' => $domain['code']],
                array_merge($domain, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $domainIds = DB::table('domains')->pluck('id', 'code');

        // ── Domain → Course mappings ────────────────────────────────
        $mappings = [
            // Foundation
            ['domain_id' => $domainIds['foundation'], 'course_code' => 'FD01', 'course_id' => '9bb06490-37cd-11ef-9470-42cc767d5507'],
            ['domain_id' => $domainIds['foundation'], 'course_code' => 'FD02', 'course_id' => '72d2dfe8-37ce-11ef-b427-ee8800c1cbc6'],
            ['domain_id' => $domainIds['foundation'], 'course_code' => 'FD03', 'course_id' => 'adc2ca6e-37ce-11ef-93b2-42cc767d5507'],

            // Legal & Ethics
            ['domain_id' => $domainIds['legal_ethics'], 'course_code' => 'LE01', 'course_id' => '68e89a60-2cef-11f0-80b8-9a5697ae9b4b'],
            ['domain_id' => $domainIds['legal_ethics'], 'course_code' => 'LE02', 'course_id' => '8b44ad9c-2cef-11f0-a355-92304f079e28'],
            ['domain_id' => $domainIds['legal_ethics'], 'course_code' => 'LE03', 'course_id' => '9ca1af5e-2cef-11f0-8068-9a5697ae9b4b'],
            ['domain_id' => $domainIds['legal_ethics'], 'course_code' => 'LE04', 'course_id' => 'ad166442-2cef-11f0-a704-92304f079e28'],

            // Crime Investigation
            ['domain_id' => $domainIds['crime_inv'], 'course_code' => 'CI01', 'course_id' => 'f0476e60-381b-11ef-b07d-42cc767d5507'],
            ['domain_id' => $domainIds['crime_inv'], 'course_code' => 'CI02', 'course_id' => '1c81e51e-381c-11ef-8e5c-42cc767d5507'],
            ['domain_id' => $domainIds['crime_inv'], 'course_code' => 'CI03', 'course_id' => '5ad6a124-381c-11ef-b6af-42cc767d5507'],
            ['domain_id' => $domainIds['crime_inv'], 'course_code' => 'CI05', 'course_id' => '5eefbc0e-89f8-11ef-97fb-9e4cfa498d33'],

            // Soft Skill Competencies
            ['domain_id' => $domainIds['soft_skills'], 'course_code' => 'SC01', 'course_id' => '8dd95c38-2cf4-11f0-bae0-92304f079e28'],
            ['domain_id' => $domainIds['soft_skills'], 'course_code' => 'SC02', 'course_id' => 'c3e7d606-2cf4-11f0-93f4-92304f079e28'],

            // Investigation Techniques
            ['domain_id' => $domainIds['inv_techniques'], 'course_code' => 'IT01', 'course_id' => 'cc319084-5758-11f0-b5d5-de63bab8aef0'],
        ];

        foreach ($mappings as $mapping) {
            DB::table('domain_courses')->updateOrInsert(
                ['domain_id' => $mapping['domain_id'], 'course_id' => $mapping['course_id']],
                array_merge($mapping, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $this->command->info('CFIP structure seeded: 3 levels, 5 domains, 14 courses mapped.');
    }
}
