<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsConfig extends Model
{
    protected $table = 'points_config';

    protected $fillable = [
        'key',
        'value',
    ];
}
