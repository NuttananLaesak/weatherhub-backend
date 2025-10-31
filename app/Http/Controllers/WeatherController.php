<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WeatherHourly;
use App\Models\WeatherDaily;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WeatherController extends Controller
{
    /**
     * Get Timezone Location frome location_id
     */
    private function getTimezone(int $locationId): string
    {
        return DB::table('locations')
            ->where('id', $locationId)
            ->value('timezone') ?? 'UTC';
    }

      /**
     *  Get Lastest Weather Realtime from Timezone Location
     */
    public function latest(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer'
        ]);

        $locationId = $request->location_id;
        $cacheKey = "latest_weather_{$locationId}";

        $data = Cache::remember($cacheKey, 60, function () use ($locationId) {

            // get timezone
            $timezone = $this->getTimezone($locationId);
            $now = Carbon::now($timezone);

            // get lat,lon from location
            $location = DB::table('locations')->find($locationId);
            if (!$location) {
                return response()->json(['error' => 'Location not found'], 404);
            }

            // fetch latest weather api
            $url = 'https://api.open-meteo.com/v1/forecast';
            $params = [
                'latitude' => $location->lat,
                'longitude' => $location->lon,
                'current_weather' => 'true', 
                'timezone' => $location->timezone,
            ];

            try {
                $response = Http::timeout(10)->retry(3, 1000)->get($url, $params);

                if (!$response->successful()) {
                    return response()->json(['error' => 'API request failed', 'message' => $response->body()], 500);
                }

                $data = $response->json();

                return [
                    'timestamp' => $data['current_weather']['time'],
                    'temp_c' => $data['current_weather']['temperature'],
                    'wind_ms' => $data['current_weather']['windspeed'],
                    'wind_direction' => $data['current_weather']['winddirection'],
                    'weather_code' => $data['current_weather']['weathercode'],
                ];
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error while fetching data from Open-Meteo API', 'message' => $e->getMessage()], 500);
            }
        });
        return response()->json($data);
    }

    /**
     *  Get Hourly Weather
     */
    public function hourly(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from'
        ]);

        $data = WeatherHourly::where('location_id', $request->location_id)
            ->whereBetween('timestamp', [$request->from, $request->to])
            ->orderBy('timestamp')
            ->get();

        return response()->json($data);
    }

    /**
     * Get Daily Weather
     */
    public function daily(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from'
        ]);

        $data = WeatherDaily::where('location_id', $request->location_id)
            ->whereBetween('date', [$request->from, $request->to])
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }
}
