<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => "super admin",
            'email' => "super_admin@gmail.com",
            'role' => 0,                        // admin
            'is_super_admin' => 1,              // super admin
            'is_blocked' => 0,                  // enabled 
            'password' => Hash::make('super_admin')
        ]);        
    }
}
