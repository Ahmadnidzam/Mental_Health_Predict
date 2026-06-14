<?php

namespace Database\Seeders;

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
        // Membuat admin default dari kredensial .env
        $this->call(AdminUserSeeder::class);

        // Memuat metrik model dari storage/models/train_results.json
        $this->call(ModelMetricSeeder::class);
    }
}
