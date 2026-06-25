<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestPcUserSeeder extends Seeder
{
    public function run(): void
    {
        // List all departments so you can pick a department_id
        $departments = \Illuminate\Support\Facades\DB::table('departments')
            ->orderBy('name')
            ->get(['department_id', 'name']);

        echo "\nAvailable departments:\n";
        foreach ($departments as $d) {
            echo "  {$d->department_id}  |  {$d->name}\n";
        }

        // Create a test PC user — change department_id to the agency you want
        $pcUser = User::updateOrCreate(
            ['user_id' => 'pc_test'],
            [
                'name'          => 'PC Test User',
                'password'      => Hash::make('password123'),
                'role'          => 'PC',
                'department_id' => $departments->first()->department_id ?? null,
            ]
        );

        echo "\nPC user created/updated:\n";
        echo "  Login ID    : {$pcUser->user_id}\n";
        echo "  Password    : password123\n";
        echo "  Role        : {$pcUser->role}\n";
        echo "  Department  : {$pcUser->department_id}\n";
        echo "  Agency name : " . ($departments->firstWhere('department_id', $pcUser->department_id)->name ?? 'n/a') . "\n\n";
    }
}
