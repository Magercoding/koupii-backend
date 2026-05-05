<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(CategorySeeder::class);

        if (!app()->environment('production') || env('SEED_DEMO_DATA', false)) {
            $this->call(TestScenarioSeeder::class);
            $this->call(DiscoverTestSeeder::class);
            $this->call(MissionSeeder::class);
        }

        $this->call(VocabularySeeder::class);
    }
}
