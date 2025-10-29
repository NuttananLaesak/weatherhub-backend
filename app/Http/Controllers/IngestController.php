<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class IngestController extends Controller
{
    public function run(Request $request)
    {
        $backfillPeriod  = now()->subDays(7)->toDateString() . ',' . now()->addDay()->toDateString();

        Artisan::call('ingest:weather', [
            '--backfill' => $backfillPeriod
        ]);
        
        return response()->json(['status' => 'ok', 'message' => 'Ingestion started']);
    }

    public function backfill(Request $request)
    {
   $request->validate([
            'location_id'=>'required|integer',
            'start'=>'required|date',
            'end'=>'required|date'
        ]);
        
        $start = $request->start;
        $end = $request->end;

         Artisan::call('ingest:weather',['--backfill'=>"$start,$end"]);

        return response()->json(['status'=>'ok','message'=>"Backfill from $start to $end started"]);
    }
}
