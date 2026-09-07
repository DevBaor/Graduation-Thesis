<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietDongHo extends Model
{
    protected $table = 'chi_tiet_dong_ho';
    protected $fillable = [
        'hoa_don_id',
        'dong_ho_id',
        'dich_vu_id',
        'chi_so_cu',
        'chi_so_moi',
        'san_luong',
        'don_gia',
        'thanh_tien'
    ];
    public $timestamps = false;

    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class, 'hoa_don_id');
    }
    public function dongHo()
    {
        return $this->belongsTo(DongHo::class, 'dong_ho_id');
    }
    public function dichVu()
    {
        return $this->belongsTo(DichVu::class, 'dich_vu_id');
    }
}
