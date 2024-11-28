<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberAktifitas extends Model
{
    use HasFactory;
    protected $connection = 'second_db';
    protected $table = 'member_aktifitas';
    protected $primaryKey = 'id';
}
