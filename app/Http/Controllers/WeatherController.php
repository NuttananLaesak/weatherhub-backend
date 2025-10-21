<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WeatherHourly;
use App\Models\WeatherDaily;
use Illuminate\Support\Facades\Cache;

class WeatherController extends Controller
{
    /**
     * ดึงข้อมูลล่าสุดแบบ hourly ของสถานที่
     */
    public function latest(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer'
        ]);

        $locationId = $request->location_id;
        $cacheKey = "latest_weather_{$locationId}";

        $data = Cache::remember($cacheKey, 60, function () use ($locationId) {
            return WeatherHourly::where('location_id', $locationId)
                ->orderByDesc('timestamp')
                ->first();
        });

        return response()->json($data);
    }

    /**
     * ดึงข้อมูล hourly ภายในช่วงวันที่
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
     * ดึงข้อมูล daily ภายในช่วงวันที่
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
