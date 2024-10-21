<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class visitor extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'visitor';
    protected $primaryKey = 'id';

    protected $fillable = [
        'guest',
        'tanggal',
        'jam',
        'menit',
        'detik',
        'status',
        'lat',
        'long',
        'halaman',
        'daerah_1',
        'parent_dataset_1',
        'dataset_1',
        'daerah_2',
        'parent_dataset_2',
        'dataset_2',
        'usia',
        'gender'
    ];
}
