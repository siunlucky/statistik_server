<?php

namespace App\Models\Otonometer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBidang extends Model
{
    use HasFactory;

    protected $connection = 'second_db';
    protected $table = 'master_bidang';
    protected $primaryKey = 'id';

    // Define accessor for 'nama' to return only the 'id'
    public function getNamaAttribute($value)
    {
        $decodedValue = json_decode($value, true);
        return $decodedValue['id'] ?? null; // Return only the 'id' field from the JSON
    }

    // Define accessor for 'description' to return only the 'id'
    public function getDescriptionAttribute($value)
    {
        $decodedValue = json_decode($value, true);
        return $decodedValue['id'] ?? null; // Return only the 'id' field from the JSON
    }

}
