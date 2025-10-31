<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;
use Illuminate\Support\Facades\Artisan;

class LocationSeeder extends Seeder
{
    public function run()
    {
        Location::create([
            'name' => 'Chiang Mai',
            'lat' => 18.7883,
            'lon' => 98.9853,
            'timezone' => 'Asia/Bangkok',
            'active' => true,
        ]);

        $backfillPeriod  = now()->subDays(7)->toDateString() . ',' . now()->addDay()->toDateString();

        Artisan::call('ingest:weather', [
            '--backfill' => $backfillPeriod
        ]);
    }
}
