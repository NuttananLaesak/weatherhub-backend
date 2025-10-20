<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeatherDaily extends Model
{
    use HasFactory;

    protected $table = 'weather_daily';

    protected $fillable = ['location_id', 'date', 'temp_min', 'temp_max', 'rain_total_mm', 'wind_max_ms'];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
