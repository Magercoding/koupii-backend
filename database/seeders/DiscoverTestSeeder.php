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
        $this->call([
            DiscoverReadingSeeder::class,
            DiscoverListeningSeeder::class,
            DiscoverWritingSeeder::class,
            DiscoverSpeakingSeeder::class,
        ]);
    }
}
