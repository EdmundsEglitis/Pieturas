<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoadSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lat1', 'lon1', 'lat2', 'lon2', 'mid_lat', 'mid_lon', 'maxspeed'
    ];
}
