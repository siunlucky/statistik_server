<?php

namespace App\Models\Otonometer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    use HasFactory;
    protected $connection = 'second_db';
    protected $table = 'halaman';
    protected $primaryKey = 'id';

    public function childs()
    {
        return $this->hasMany(Bidang::class, 'id_parent', 'id')->orderBy('kode');
    }

    public function parent()
    {
        return $this->hasOne(Bidang::class, 'id', 'id_parent');
    }
}
