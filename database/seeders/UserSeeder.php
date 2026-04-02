<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'admin@koupii.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'avatar' => null,
                'bio' => 'Administrator account'
            ]
        );

        // Teacher 1
        User::updateOrCreate(
            ['email' => 'teacher@koupii.com'],
            [
                'name' => 'Teacher User 1',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'avatar' => null,
                'bio' => 'Teacher account 1'
            ]
        );

        // Teacher 2
        User::updateOrCreate(
            ['email' => 'teacher2@koupii.com'],
            [
                'name' => 'Teacher User 2',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'avatar' => null,
                'bio' => 'Teacher account 2'
            ]
        );

        // Student 1
        User::updateOrCreate(
            ['email' => 'student1@koupii.com'],
            [
                'name' => 'Student User 1',
                'password' => Hash::make('password'),
                'role' => 'student',
                'avatar' => null,
                'bio' => 'Student account 1'
            ]
        );

        // Student 2
        User::updateOrCreate(
            ['email' => 'student2@koupii.com'],
            [
                'name' => 'Student User 2',
                'password' => Hash::make('password'),
                'role' => 'student',
                'avatar' => null,
                'bio' => 'Student account 2'
            ]
        );
    }
}
