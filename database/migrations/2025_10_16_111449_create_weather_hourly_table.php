<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('weather_hourly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->dateTime('timestamp'); // UTC
            $table->float('temp_c');
            $table->float('humidity');
            $table->float('wind_ms');
            $table->float('rain_mm');
            $table->integer('weather_code');
            $table->unique(['location_id', 'timestamp']); // ป้องกันซ้ำ
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_hourly');
    }
};
