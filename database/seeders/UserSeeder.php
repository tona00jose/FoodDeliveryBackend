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
        
        User::insert([            
            [ 'name' => "aaa", 'email' => 'aaa@gmail.com', 'role' => 0, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "bbb", 'email' => 'bbb@gmail.com', 'role' => 1, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "ccc", 'email' => 'ccc@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "ddd", 'email' => 'ddd@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "eee", 'email' => 'eee@gmail.com', 'role' => 1, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "fff", 'email' => 'fff@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "ggg", 'email' => 'ggg@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "hhh", 'email' => 'hhh@gmail.com', 'role' => 1, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "iii", 'email' => 'iii@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "jjj", 'email' => 'jjj@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "kkk", 'email' => 'kkk@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "aab", 'email' => 'aab@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "aac", 'email' => 'aac@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "bac", 'email' => 'bac@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ],
            [ 'name' => "bad", 'email' => 'bad@gmail.com', 'role' => 2, 'is_super_admin' => 0, 'is_blocked' => 0,'password' => Hash::make('123456789') ]
        ]);
        
    }
}
