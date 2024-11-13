<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class halaman extends Model
{
    protected $connection = 'mysql';

    use HasFactory;
    protected $table = 'halaman';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nama'
    ];
}
