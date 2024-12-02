<?php

namespace App\Models\Otonometer;

use Carbon\Carbon;
use App\Models\Otonometer\Wilayah;
use Illuminate\Database\Eloquent\Model;
use App\Models\Otonometer\MemberAktifitas;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;
    protected $connection = 'second_db';
    protected $table = 'member';
    protected $primaryKey = 'id';

    public function wilayah(){
        return $this->belongsTo(Wilayah::class, 'id_wilayah', 'id');
    }

    public function lastActivity(){
        return $this->hasOne(MemberAktifitas::class, 'id_member', 'id')->latest();
    }

    public function getAgeAttribute()
    {
        if (!$this->tanggal_lahir) {
            return null;
        }

        return Carbon::parse($this->tanggal_lahir)->age;
    }

    public function getGenderAttribute(){
        if (strtolower($this->title) == 'mr' || strtolower($this->title) == 'tn' || strtolower($this->title) == 'Tuan') {
            return 'L';
        } else if (strtolower($this->title) == 'nn' || strtolower($this->title) == 'ny' || strtolower($this->title) == 'nona' || strtolower($this->title) == 'nyonya') {
            return 'P';
        }

        return 'unknown';
    }

    public function getCreatedAtHourAttribute(){
        return Carbon::parse($this->created_at)->format('H');
    }

    public function getCreatedAtMinuteAttribute(){
        return Carbon::parse($this->created_at)->format('i');
    }

    public function getCreatedAtSecondAttribute(){
        return Carbon::parse($this->created_at)->format('s');
    }

    public function getCreatedAtDateAttribute(){
        return Carbon::parse($this->created_at)->format('Y-m-d');
    }

    public function getVerifiedStatusAttribute(){
        return $this->email_verified_at != null ? 'Verified' : 'Unverified';
    }

    // public function getWilayahAttribute(){
    //     return $this->with('wilayah')->nama;
    // }

    protected $appends = [
        'age',
        'gender',
        'created_at_date',
        'created_at_hour',
        'created_at_minute',
        'created_at_second',
        'verified_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
