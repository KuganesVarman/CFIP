<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BnmPcUserSeeder extends Seeder
{
    public function run(): void
    {
        $departmentId = 'abc6c14b-f4de-11ef-ae24-12931a83d691';

        $pcUser = User::updateOrCreate(
            ['user_id' => 'pc_bnm'],
            [
                'name'          => 'PC Bank Negara Malaysia',
                'password'      => Hash::make('password123'),
                'role'          => 'PC',
                'department_id' => $departmentId,
            ]
        );

        echo "\nPC user created/updated:\n";
        echo "  Login ID    : {$pcUser->user_id}\n";
        echo "  Password    : password123\n";
        echo "  Role        : {$pcUser->role}\n";
        echo "  Department  : {$pcUser->department_id}\n\n";
    }
}
