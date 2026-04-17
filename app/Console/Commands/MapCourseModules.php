<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Module;
use App\Models\CourseModuleMap;

class MapCourseModules extends Command
{
    protected $signature = 'map:course-modules';
    protected $description = 'Create clean mapping table of course_id and its modules';

    public function handle()
    {
        $this->info("Mapping course_id → modules...");

        $allModules = Module::all();

        foreach ($allModules as $m) {
            CourseModuleMap::updateOrCreate(
                [
                    'course_id' => $m->course_id,
                    'module_id' => $m->module_id
                ],
                [
                    'module_title' => $m->title
                ]
            );
        }

        $this->info("Done! Clean course-module map created.");
        return Command::SUCCESS;
    }
}
