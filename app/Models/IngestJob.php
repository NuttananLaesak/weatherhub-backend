<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class IngestJob extends Model {
    protected $fillable = ['location_id','type','status','note'];
}
