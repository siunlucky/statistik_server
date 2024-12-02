<?php

namespace App\Models\Otonometer;

use App\Models\Otonometer\Wilayah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Halaman extends Model
{
    use HasFactory;
    protected $connection = 'second_db';
    protected $table = 'halaman';
    protected $primaryKey = 'id';

    public function wilayahSatu() {
        return $this->belongsTo(Wilayah::class, 'id_wilayah_1');
    }

    public function wilayahDua() {
        return $this->belongsTo(Wilayah::class, 'id_wilayah_2');
    }
}
