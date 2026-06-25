<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CfipAcademyLearnerSeeder extends Seeder
{
    public function run(): void
    {
        $learner = User::updateOrCreate(
            ['user_id' => 'cfip_learner'],
            [
                'name'          => 'CFIP Academy Learner',
                'password'      => Hash::make('CFIPLearner@2024'),
                'role'          => 'L',
                'department_id' => '4cb49ade-25ba-11ef-854d-3afb2a5f5864',
            ]
        );

        $this->command->info('Learner account created/updated:');
        $this->command->line("  Login ID   : {$learner->user_id}");
        $this->command->line('  Password   : CFIPLearner@2024');
        $this->command->line("  Role       : {$learner->role}");
        $this->command->line("  Department : {$learner->department_id}");
    }
}
