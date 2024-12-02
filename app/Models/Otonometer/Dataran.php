<?php

namespace App\Models\Otonometer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dataran extends Model
{
    use HasFactory;
    protected $connection = 'second_db';
    protected $table = 'halaman';
    protected $primaryKey = 'id';
}
