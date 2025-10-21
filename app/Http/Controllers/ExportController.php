<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WeatherHourly;
use App\Models\WeatherDaily;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function exportCsv(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'from' => 'required|date',
            'to' => 'required|date',
            'type' => 'required|in:hourly,daily'
        ]);

        $locationId = $request->location_id;
        $from = $request->from;
        $to = $request->to;
        $type = $request->type;

        $filename = "{$type}_weather_{$from}_to_{$to}.csv";

        $response = new StreamedResponse(function () use ($locationId, $from, $to, $type) {
            $handle = fopen('php://output', 'w');

            if ($type === 'hourly') {
                fputcsv($handle, ['timestamp','temp_c','humidity','wind_ms','rain_mm','weather_code']);
                $data = WeatherHourly::where('location_id',$locationId)
                    ->whereBetween('timestamp', [$from, $to])
                    ->orderBy('timestamp')
                    ->cursor();

                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row->timestamp,
                        $row->temp_c,
                        $row->humidity,
                        $row->wind_ms,
                        $row->rain_mm,
                        $row->weather_code
                    ]);
                }
            } else { // daily
                fputcsv($handle, ['date','temp_min','temp_max','rain_total_mm','wind_max_ms']);
                $data = WeatherDaily::where('location_id',$locationId)
                    ->whereBetween('date', [$from, $to])
                    ->orderBy('date')
                    ->cursor();

                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row->date,
                        $row->temp_min,
                        $row->temp_max,
                        $row->rain_total_mm,
                        $row->wind_max_ms
                    ]);
                }
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type','text/csv');
        $response->headers->set('Content-Disposition',"attachment; filename={$filename}");

        return $response;
    }
}
