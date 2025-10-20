<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WeatherHourly;
use App\Models\WeatherDaily;
use Illuminate\Support\Facades\Cache;

class WeatherController extends Controller
{
    public function latest(Request $request)
    {
        $request->validate(['location_id' => 'required|integer']);

        $cacheKey = "latest_weather_{$request->location_id}";
        $data = Cache::remember($cacheKey, 60, function () use ($request) {
            return WeatherHourly::where('location_id', $request->location_id)
                ->latest('timestamp')
                ->first();
        });

        return response()->json($data);
    }
    public function hourly(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'from' => 'required|date',
            'to' => 'required|date'
        ]);
        $data = WeatherHourly::where('location_id', $request->location_id)
            ->whereBetween('timestamp', [$request->from, $request->to])
            ->get();
        return response()->json($data);
    }

    public function daily(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'from' => 'required|date',
            'to' => 'required|date'
        ]);
        $data = WeatherDaily::where('location_id', $request->location_id)
            ->whereBetween('date', [$request->from, $request->to])
            ->get();
        return response()->json($data);
    }
}
