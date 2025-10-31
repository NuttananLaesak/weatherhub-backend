<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Run UserSeeder
        $this->call(UserSeeder::class);

        // Run LocationSeeder
        $this->call(LocationSeeder::class);
    }
}
