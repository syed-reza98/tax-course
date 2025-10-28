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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'text@example.com',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        User::factory()->create([
            'name' => 'Instructor User',
            'email' => 'instructor@example.com',
            'role' => 'instructor',
            'password' => bcrypt('password123'),
        ]);

        User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@example.com',
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);
    }
}
