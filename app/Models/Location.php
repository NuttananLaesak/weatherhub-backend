<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model {

     use HasFactory;

    protected $fillable = ['name','lat','lon','timezone','active'];

    public function weatherHourly() {
        return $this->hasMany(WeatherHourly::class);
    }

    public function weatherDaily() {
        return $this->hasMany(WeatherDaily::class);
    }
}
