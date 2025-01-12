<?php

namespace App\Models\Otonometer;

use App\Models\Otonometer\Dataran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wilayah extends Model
{
    use HasFactory;
    protected $connection = 'second_db';
    protected $table = 'master_wilayah';
    protected $primaryKey = 'id';

    public function cities()
    {
        return $this->hasMany(Wilayah::class, 'id_parent', 'id')->orderBy('nama');
    }

    public function province()
    {
        return $this->belongsTo(Wilayah::class, 'id_parent', 'id')->orderBy('nama');
    }

    public function masterDataran()
    {
        return $this->hasOne(Dataran::class, 'id', 'id_dataran');
    }

    public function getNamaAttribute($value)
    {
        $decodedValue = json_decode($value, true);
        return $decodedValue['id'] ?? null;
    }
}
