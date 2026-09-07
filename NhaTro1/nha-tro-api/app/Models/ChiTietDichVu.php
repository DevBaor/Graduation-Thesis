<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietDichVu extends Model
{
    protected $table = 'chi_tiet_dich_vu';
    protected $fillable = ['hoa_don_id', 'dich_vu_id', 'so_luong', 'don_gia', 'thanh_tien'];
    public $timestamps = false;

    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class, 'hoa_don_id');
    }
    public function dichVu()
    {
        return $this->belongsTo(DichVu::class, 'dich_vu_id');
    }
}
