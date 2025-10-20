<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Location;
use App\Models\WeatherHourly;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WeatherTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_weather_hourly()
    {
        $location = Location::factory()->create();
        $time = now();

        WeatherHourly::updateOrCreate(
            ['location_id'=>$location->id,'timestamp'=>$time],
            ['temp_c'=>25,'humidity'=>60,'wind_ms'=>2,'rain_mm'=>0,'weather_code'=>1]
        );

        $this->assertDatabaseHas('weather_hourly',['location_id'=>$location->id,'timestamp'=>$time]);
    }
}
