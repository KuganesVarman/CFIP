<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        User::where('user_id', 'KuganesVarman')->delete();

        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $special = '@#$!';

        $pw  = $upper[random_int(0, strlen($upper)-1)];
        $pw .= $upper[random_int(0, strlen($upper)-1)];
        $pw .= $lower[random_int(0, strlen($lower)-1)];
        $pw .= $lower[random_int(0, strlen($lower)-1)];
        $pw .= $lower[random_int(0, strlen($lower)-1)];
        $pw .= $digits[random_int(0, strlen($digits)-1)];
        $pw .= $digits[random_int(0, strlen($digits)-1)];
        $pw .= $special[random_int(0, strlen($special)-1)];
        $all = $upper . $lower . $digits . $special;
        while (strlen($pw) < 10) {
            $pw .= $all[random_int(0, strlen($all)-1)];
        }
        $password = str_shuffle($pw);

        User::create([
            'user_id'              => 'KuganesVarman',
            'name'                 => 'Kuganes Varman',
            'email'                => 'kuganesvarman@graduate.utm.my',
            'role'                 => 'L',
            'password'             => Hash::make($password),
            'must_change_password' => true,
        ]);

        $this->command->info('=== Test user created ===');
        $this->command->info("Username : KuganesVarman");
        $this->command->info("Password : {$password}");
        $this->command->info("Email    : kuganesvarman@graduate.utm.my");
        $this->command->info("Role     : Learner (must change password on first login)");
    }
}
