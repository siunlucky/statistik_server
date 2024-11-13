<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class guest extends Model
{
    protected $connection = 'mysql';

    use HasFactory;
    use SoftDeletes;
    protected $table = 'guest';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'id'
    ];
}
