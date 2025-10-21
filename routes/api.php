<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\IngestController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ExportController;

Route::post('/auth/login',[AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/locations',[LocationController::class,'index']);
    Route::post('/locations',[LocationController::class,'store']);
    Route::patch('/locations/{id}',[LocationController::class,'update']);

    Route::post('/ingest/run',[IngestController::class,'run']);
    Route::post('/ingest/backfill',[IngestController::class,'backfill']);

    Route::get('/weather/latest',[WeatherController::class,'latest']);
    Route::get('/weather/hourly',[WeatherController::class,'hourly']);
    Route::get('/weather/daily',[WeatherController::class,'daily']);

    Route::get('/health',[HealthController::class,'index']);
    Route::get('/export/csv', [ExportController::class, 'exportCsv']);
});