<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DiscoverTestSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production') && !env('SEED_DEMO_DATA', false)) {
            $this->command?->warn('Skipping DiscoverTestSeeder in production.');
            return;
        }

        $this->call([
            DiscoverReadingSeeder::class,
            DiscoverListeningSeeder::class,
            DiscoverWritingSeeder::class,
            DiscoverSpeakingSeeder::class,
        ]);
    }
}
