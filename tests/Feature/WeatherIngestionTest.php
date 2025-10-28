<?php

namespace Tests\Feature;

use Tests\TestCase;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Location;
use App\Models\WeatherHourly;
use App\Models\WeatherDaily;
use Carbon\Carbon;

class WeatherIngestionTest extends TestCase
{
    // use RefreshDatabase;

    /** @test */
    public function it_upserts_hourly_weather_without_duplicates()
    {
        $location = Location::factory()->create([
            'lat' => 18.7883,
            'lon' => 98.9853,
            'timezone' => 'Asia/Bangkok',
        ]);

        $timestamp = Carbon::now()->toDateTimeString();

        // Insert initial record
        WeatherHourly::updateOrCreate(
            ['location_id' => $location->id, 'timestamp' => $timestamp],
            ['temp_c'=>25,'humidity'=>60,'wind_ms'=>2,'rain_mm'=>0,'weather_code'=>1]
        );

        // Try upsert again
        WeatherHourly::updateOrCreate(
            ['location_id' => $location->id, 'timestamp' => $timestamp],
            ['temp_c'=>26,'humidity'=>65,'wind_ms'=>3,'rain_mm'=>1,'weather_code'=>2]
        );

        $this->assertDatabaseCount('weather_hourly', 1);

        $this->assertDatabaseHas('weather_hourly', [
            'location_id' => $location->id,
            'timestamp' => $timestamp,
            'temp_c' => 26,
            'humidity' => 65,
            'wind_ms' => 3,
            'rain_mm' => 1,
            'weather_code' => 2
        ]);
    }

    /** @test */
    public function it_calculates_daily_summary_correctly()
    {
        $location = Location::factory()->create();

        $date = Carbon::today();
        $timestamps = [
            $date->copy()->hour(0),
            $date->copy()->hour(6),
            $date->copy()->hour(12),
            $date->copy()->hour(18)
        ];

        // Seed hourly data
        foreach ($timestamps as $i => $time) {
            WeatherHourly::create([
                'location_id' => $location->id,
                'timestamp' => $time,
                'temp_c' => 20 + $i,
                'humidity' => 50 + $i,
                'wind_ms' => 2 + $i,
                'rain_mm' => 1 + $i,
                'weather_code' => 0
            ]);
        }

        // Run daily summary
        $hourlyData = WeatherHourly::where('location_id', $location->id)
            ->whereDate('timestamp', $date->toDateString())
            ->get();

        $daily = WeatherDaily::updateOrCreate(
            ['location_id' => $location->id, 'date' => $date->toDateString()],
            [
                'temp_min' => $hourlyData->min('temp_c'),
                'temp_max' => $hourlyData->max('temp_c'),
                'rain_total_mm' => $hourlyData->sum('rain_mm'),
                'wind_max_ms' => $hourlyData->max('wind_ms')
            ]
        );

        $this->assertEquals(20, $daily->temp_min);
        $this->assertEquals(23, $daily->temp_max);
        $this->assertEquals(10, $daily->rain_total_mm); 
        $this->assertEquals(5, $daily->wind_max_ms);
    }
}
