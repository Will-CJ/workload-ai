<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin PT ABC', 'email' => 'admin@ptabc.test'],
            ['name' => 'Viewer PT ABC', 'email' => 'viewer@ptabc.test'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);
            $user->forceFill([
                'name' => $data['name'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
