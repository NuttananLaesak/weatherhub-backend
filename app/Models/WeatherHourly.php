<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeatherHourly extends Model
{
    use HasFactory;

    protected $table = 'weather_hourly';

    protected $fillable = ['location_id', 'timestamp', 'temp_c', 'humidity', 'wind_ms', 'rain_mm', 'weather_code'];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
