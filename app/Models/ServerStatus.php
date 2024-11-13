<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerStatus extends Model
{
    protected $connection = 'mysql';
    protected $table = 'server_status';

    // change the timestamp column to datetime
    protected $casts = [
        'cpu_usage' => 'float',
        'memory_usage' => 'float',
        'disk_usage' => 'float',
        'timestamp' => 'datetime',
    ];
}
