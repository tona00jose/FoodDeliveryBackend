<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        // $this->call(FactorySeeder::class);
        // $this->call(TeamSeeder::class);
        // $this->call(DivisionSeeder::class);
        // $this->call(OrganizationSeeder::class);
        // $this->call(EmployeeSeeder::class);
        // $this->call(WorkTypeSeeder::class);
        // $this->call(EmployeeHistorySeeder::class);
        // $this->call(EmployeeWorksheetSeeder::class);
        
        // $this->call(UserRoleSeeder::class);
    }
}
