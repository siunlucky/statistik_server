<?php

namespace App\Models\Otonometer;

use App\Models\Otonometer\Wilayah;
use App\Models\Otonometer\MasterBidang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Halaman extends Model
{
    use HasFactory;

    protected $connection = 'second_db';
    protected $table = 'halaman';
    protected $primaryKey = 'id';

    // protected $with = ['wilayahSatu', 'wilayahDua'];
    // protected $with = ['parent_1', 'parent_2'];
    protected $appends = ['dataset_1_data', 'dataset_2_data'];
    protected $with = ['parent_1', 'parent_2'];


    public function wilayahSatu()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah_1');
    }

    public function wilayahDua()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah_2');
    }

    /**
     * Accessor for dataset_1 to decode JSON.
     */
    public function getDataset1Attribute($value)
    {
        return json_decode(json_decode($value), true) ?: ($value ?? []);
    }

    /**
     * Accessor for dataset_2 to decode JSON.
     */
    public function getDataset2Attribute($value)
    {
        return json_decode(json_decode($value), true) ?: ($value ?? []);
    }

    /**
     * Relationship to MasterBidang based on dataset_1.
     */
    public function masterBidangs()
    {
        if (!is_array($this->dataset_1) && json_decode($this->dataset_1) === "semua"){
            // dd("masuk");
            return [[
                'id' => 'semua',
                'nama' => 'Semua',
                'id_parent' => $this->parent_1,
                'level' => 3,
                'id_increament' => 0
            ]];
        }
        return MasterBidang::whereIn('id', $this->dataset_1)->select('id', 'nama', 'id_parent', 'level', 'id_increament')->get();
    }

    /**
     * Relationship to MasterBidang based on dataset_2.
     */
    public function masterBidangsDataset2()
    {
        if (!is_array($this->dataset_2) && json_decode($this->dataset_2) === "semua"){
            // dd("masuk");
            return [[
                'id' => 'semua',
                'nama' => 'Semua',
                'id_parent' => $this->parent_2,
                'level' => 3,
                'id_increament' => 0
            ]];
        }

        return MasterBidang::whereIn('id', $this->dataset_2)->select('id', 'nama', 'id_parent', 'level', 'id_increament')->get();
    }

    /**
     * Append the dataset_1_data relationship to the model.
     */
    public function getDataset1DataAttribute()
    {
        return $this->masterBidangs();
    }

    /**
     * Append the dataset_2_data relationship to the model.
     */
    public function getDataset2DataAttribute()
    {
        return $this->masterBidangsDataset2();
    }

    public function parent_1()
    {
        return $this->belongsTo(MasterBidang::class, 'parent_1', 'id')->select('id', 'nama', 'id_parent', 'level', 'id_increament');

    }

    public function parent_2()
    {
        return $this->belongsTo(MasterBidang::class, 'parent_2', 'id')->select('id', 'nama', 'id_parent', 'level', 'id_increament');
    }

    // public function getParent1Attribute()
    // {
    //     return $this->parent_1;
    // }

    // public function getParent2Attribute()
    // {
    //     return $this->parent_2;
    // }
}

