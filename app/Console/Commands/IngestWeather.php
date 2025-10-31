<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Location;
use App\Models\WeatherHourly;
use App\Models\WeatherDaily;
use App\Models\IngestJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
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

            // Rate-limit key per location
            $rateKey = 'ingest:' . $location->id;
            if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                $this->warn("Rate limit reached for location {$location->name}, skipping...");
                continue;
            }
            RateLimiter::hit($rateKey, 3600); // reset 1 hour

            // Choose Start and End
            if ($this->option('backfill')) {
                [$startDate, $endDate] = explode(',', $this->option('backfill'));
                $startDate = Carbon::parse($startDate);
                $endDate = Carbon::parse($endDate);
            } else {
                $timezone = $location->timezone;
                $startDate = Carbon::today($timezone)->subDays(7);
                $endDate = Carbon::today($timezone)->addDay();
            }

            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                $this->info("Processing {$location->name} - Date: " . $date->toDateString());

                // Check Data Hourly is Already Have ?
                $existingHourlyData = WeatherHourly::where('location_id', $location->id)
                    ->whereDate('timestamp', $date->toDateString())
                    ->exists();

                if ($existingHourlyData) {
                    $this->info("Hourly data already exists for {$location->name} on {$date->toDateString()}, skipping hourly ingestion.");
                    continue; // Skip If Hourly is Already Have
                }

                $ingestJob = IngestJob::create([
                    'location_id' => $location->id,
                    'type' => 'hourly',
                    'status' => 'pending',
                    'note' => null,
                ]);

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
                    $response = Http::timeout(10)->retry(3, 1000)->get($url, $params);

                    if (!$response->successful()) {
                        $note = "Failed to fetch data: " . $response->status();
                        $ingestJob->update(['status' => 'failed', 'note' => $note]);
                        $this->error($note);
                        continue;
                    }

                    $data = $response->json();
                    $hourlyCount = isset($data['hourly']['time']) ? count($data['hourly']['time']) : 0;
                    $this->info("Hourly records: " . $hourlyCount);

                    if ($hourlyCount === 0) {
                        $ingestJob->update(['status' => 'failed', 'note' => 'No hourly data']);
                        continue;
                    }

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

                    $hourlyData = WeatherHourly::where('location_id', $location->id)
                        ->whereDate('timestamp', $date->toDateString())
                        ->get();

                    // Check Data Daily is Already Have ?
                    $existingDailyData = WeatherDaily::where('location_id', $location->id)
                        ->whereDate('date', $date->toDateString())
                        ->exists();

                    if ($existingDailyData) {
                        $this->info("Daily data already exists for {$location->name} on {$date->toDateString()}, skipping daily ingestion.");
                        continue; // Skip If Dairy is Already Have
                    }

                    WeatherDaily::updateOrCreate(
                        ['location_id' => $location->id, 'date' => $date->toDateString()],
                        [
                            'temp_min' => $hourlyData->min('temp_c'),
                            'temp_max' => $hourlyData->max('temp_c'),
                            'rain_total_mm' => $hourlyData->sum('rain_mm'),
                            'wind_max_ms' => $hourlyData->max('wind_ms')
                        ]
                    );

                    $ingestJob->update(['status' => 'success']);
                    $this->info("Daily summary updated for {$location->name}");
                } catch (\Exception $e) {
                    $ingestJob->update(['status' => 'failed', 'note' => $e->getMessage()]);
                    $this->error("Error: " . $e->getMessage());
                }
            }
        }

        $this->info("Ingestion finished.");
    }
}
