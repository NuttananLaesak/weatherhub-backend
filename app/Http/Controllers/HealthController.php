<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    public function index()
    {
        $dbOk = false;
        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch(\Exception $e){
            $dbOk = false;
        }

        $cacheOk = Cache::set('health_check', now(), 10);

        return response()->json([
            'database' => $dbOk ? 'ok':'fail',
            'cache' => $cacheOk ? 'ok':'fail',
            'scheduler' => 'Check manually in production'
        ]);
    }
}
