<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CfipAcademyPcSeeder extends Seeder
{
    public function run(): void
    {
        $pcUser = User::updateOrCreate(
            ['user_id' => 'cfip_pc'],
            [
                'name'          => 'Program Coordinator CFIP Academy',
                'password'      => Hash::make('CFIPAcademy@2024'),
                'role'          => 'PC',
                'department_id' => '4cb49ade-25ba-11ef-854d-3afb2a5f5864',
            ]
        );

        $this->command->info('PC user created/updated:');
        $this->command->line("  Login ID   : {$pcUser->user_id}");
        $this->command->line('  Password   : CFIPAcademy@2024');
        $this->command->line("  Role       : {$pcUser->role}");
        $this->command->line("  Department : {$pcUser->department_id}");
    }
}
