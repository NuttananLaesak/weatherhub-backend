<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Location;
use App\Models\WeatherHourly;
use App\Models\WeatherDaily;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class IngestWeather extends Command
{
    protected $signature = 'ingest:weather {--backfill= : yyyy-mm-dd,yyyy-mm-dd}';
    protected $description = 'Ingest weather data from Open-Meteo';

    public function handle()
    {
        $this->info("Start ingestion...");

        $locations = Location::where('active', true)->get();
        $this->info("Active locations count: " . $locations->count());

        foreach ($locations as $location) {
            $this->info("Processing location: {$location->name} ({$location->lat}, {$location->lon})");

            // กำหนดวันที่เริ่มต้นและสิ้นสุด
            if ($this->option('backfill')) {
                [$startDate, $endDate] = explode(',', $this->option('backfill'));
                $startDate = Carbon::parse($startDate);
                $endDate = Carbon::parse($endDate);
            } else {
                $timezone = $location->timezone;
                $startDate = Carbon::today($timezone)->subDays(7); // 7 วันก่อนหน้า
                $endDate = Carbon::today($timezone)->addDay();    // วันถัดไป
            }

            // Loop วัน
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                $this->info("Processing {$location->name} - Date: " . $date->toDateString());

                $url = 'https://api.open-meteo.com/v1/forecast';
                $params = [
                    'latitude' => $location->lat,
                    'longitude' => $location->lon,
                    'hourly' => 'temperature_2m,relativehumidity_2m,precipitation,windspeed_10m,weathercode',
                    'timezone' => $location->timezone,
                    'start_date' => $date->toDateString(),
                    'end_date' => $date->toDateString()
                ];

                $this->info("Fetching: " . $url . '?' . http_build_query($params));

                try {
                    $response = Http::get($url, $params);

                    if (!$response->successful()) {
                        $this->error("Failed to fetch data for {$location->name} - {$date->toDateString()}");
                        continue; // ข้ามวันนี้ ไปวันถัดไป
                    }

                    $data = $response->json();

                    $hourlyCount = isset($data['hourly']['time']) ? count($data['hourly']['time']) : 0;
                    $this->info("Hourly records: " . $hourlyCount);

                    if ($hourlyCount === 0) continue;

                    // Update hourly
                    foreach ($data['hourly']['time'] as $i => $time) {
                        WeatherHourly::updateOrCreate(
                            ['location_id' => $location->id, 'timestamp' => $time],
                            [
                                'temp_c' => $data['hourly']['temperature_2m'][$i],
                                'humidity' => $data['hourly']['relativehumidity_2m'][$i],
                                'wind_ms' => $data['hourly']['windspeed_10m'][$i],
                                'rain_mm' => $data['hourly']['precipitation'][$i],
                                'weather_code' => $data['hourly']['weathercode'][$i],
                            ]
                        );
                    }

                    // Update daily summary
                    $hourlyData = WeatherHourly::where('location_id', $location->id)
                        ->whereDate('timestamp', $date->toDateString())
                        ->get();

                    WeatherDaily::updateOrCreate(
                        ['location_id' => $location->id, 'date' => $date->toDateString()],
                        [
                            'temp_min' => $hourlyData->min('temp_c'),
                            'temp_max' => $hourlyData->max('temp_c'),
                            'rain_total_mm' => $hourlyData->sum('rain_mm'),
                            'wind_max_ms' => $hourlyData->max('wind_ms')
                        ]
                    );

                    $this->info("Daily summary updated for {$location->name}");
                } catch (\Exception $e) {
                    $this->error("Error fetching {$location->name} - {$date->toDateString()}: " . $e->getMessage());
                    continue; // ข้ามวัน/ข้าม location ต่อไป
                }
            }
        }

        $this->info("Ingestion finished.");
    }
}
