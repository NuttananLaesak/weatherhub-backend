<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngestJob extends Model
{
    use HasFactory;
    
    protected $fillable = ['location_id', 'type', 'status', 'note'];
}
