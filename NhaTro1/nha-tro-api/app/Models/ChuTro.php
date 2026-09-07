<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ChuTro extends Model
{
    protected $table = 'chu_tro';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'dia_chi',
        'bank_code',
        'account_no',
        'account_name',
    ];

    // ✅ ĐÚNG: dùng hasOne (PK dùng chung)
    public function nguoiDung()
    {
        return $this->hasOne(
            NguoiDung::class,
            'id', // PK bên nguoi_dung
            'id'  // PK bên chu_tro
        );
    }

    public function dayTro()
    {
        return $this->hasMany(DayTro::class, 'chu_tro_id');
    }
}
