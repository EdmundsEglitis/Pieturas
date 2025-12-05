<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusStop extends Model
{
    use HasFactory;

    // ✅ Add fillable fields for mass assignment
    protected $fillable = [
        'name',
        'stop_code',
        'unified_code',
        'latitude',
        'longitude',
        'road_side',
        'road_number',
        'street',
        'municipality',
        'parish',
        'village',
    ];
}
