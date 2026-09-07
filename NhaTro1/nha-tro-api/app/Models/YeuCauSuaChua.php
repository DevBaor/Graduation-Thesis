<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YeuCauSuaChua extends Model
{
    protected $table = 'yeu_cau_sua_chua';

    protected $fillable = [
        'khach_thue_id',
        'phong_id',
        'loai',
        'mo_ta',
        'trang_thai',
    ];

    // dùng created_at / updated_at chuẩn
    public $timestamps = true;

    public function khachThue()
    {
        return $this->belongsTo(KhachThue::class, 'khach_thue_id');
    }

    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }
}
