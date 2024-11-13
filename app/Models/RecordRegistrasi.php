<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecordRegistrasi extends Model
{
    protected $connection = 'mysql';

    use HasFactory;
    use SoftDeletes;

    protected $table = 'record_registrasi';
    protected $primaryKey = 'id';

    protected $fillable = [
        'guest',
        'gender',
        'age',
        'province',
        'city'
    ];
}
