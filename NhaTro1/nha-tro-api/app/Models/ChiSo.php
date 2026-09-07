<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiSo extends Model
{
    protected $table = 'chi_so';
    protected $fillable = [
        'dong_ho_id',
        'thoi_gian',
        'gia_tri',
        'ghi_chu',
        'nguoi_nhap_id',
        'nguoi_sua_id',
        'ngay_tao',
        'updated_at',
    ];


    // ❌ KHÔNG dùng timestamps vì bảng không có updated_at
    public $timestamps = false;

    public function dongHo()
    {
        return $this->belongsTo(DongHo::class, 'dong_ho_id');
    }

    public function nguoiNhap()
    {
        return $this->belongsTo(User::class, 'nguoi_nhap_id');
    }

    // Lấy thông tin phòng qua đồng hồ
    public function phong()
    {
        return $this->hasOneThrough(
            Phong::class,
            DongHo::class,
            'id',
            'id',
            'dong_ho_id',
            'phong_id'
        );
    }

    public function nguoiSua()
    {
        return $this->belongsTo(User::class, 'nguoi_sua_id');
    }
}
