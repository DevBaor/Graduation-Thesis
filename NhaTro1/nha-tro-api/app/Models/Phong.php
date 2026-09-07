<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\DichVuDinhKy;
class Phong extends Model
{
    protected $table = 'phong';
    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';
    protected $fillable = ['day_tro_id', 'so_phong', 'gia', 'trang_thai', 'suc_chua', 'dien_tich', 'tang'];
    public function dayTro()
    {
        return $this->belongsTo(DayTro::class, 'day_tro_id');
    }
    public function tienIch()
    {
        return $this->belongsToMany(TienIch::class, 'phong_tien_ich', 'phong_id', 'tien_ich_id');
    }
    public function hopDongs()
    {
        return $this->hasMany(HopDong::class, 'phong_id');
    }
    public function baiDang()
    {
        return $this->hasMany(BaiDang::class, 'phong_id');
    }
    public function dongHo()
    {
        return $this->hasMany(DongHo::class, 'phong_id');
    }
    public function yeuCauThue()
    {
        return $this->hasMany(YeuCauThue::class, 'phong_id');
    }
    public function dich_vu_dinh_ky()
    {
        return $this->hasMany(DichVuDinhKy::class, 'phong_id');
    }

    /**
     * Alias camelCase cho tiện dùng
     */
    public function dichVuDinhKy()
    {
        return $this->dich_vu_dinh_ky();
    }
}
