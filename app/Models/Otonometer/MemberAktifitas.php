<?php

namespace App\Models\Otonometer;

use App\Models\Otonometer\Member;
use App\Models\Otonometer\Halaman;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MemberAktifitas extends Model
{
    use HasFactory;
    protected $connection = 'second_db';
    protected $table = 'member_aktifitas';
    protected $primaryKey = 'id';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function member()
    {
        return $this->belongsTo(Member::class, 'id_member');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     **/
    public function halaman()
    {
        return $this->hasOne(Halaman::class,'id','id_halaman');
    }
}
