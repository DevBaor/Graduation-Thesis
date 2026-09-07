<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaiDang extends Model
{
    use HasFactory;

    protected $table = 'bai_dang';
    public $timestamps = false;
    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'nguoi_dung_id',
        'phong_id',
        'tieu_de',
        'mo_ta',
        'gia_niem_yet',
        'dia_chi',
        'trang_thai',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    protected $appends = [
        'trang_thai_label',
        'trang_thai_color',
    ];

    // ==========================
    // Quan hệ
    // ==========================
    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }

    public function chuTro()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function anh()
    {
        return $this->hasMany(AnhBaiDang::class, 'bai_dang_id');
    }

    // ==========================
    // Accessor trạng thái
    // ==========================
    public function getTrangThaiLabelAttribute()
    {
        return match ($this->trang_thai) {
            'nhap' => 'Nháp',
            'cho_duyet' => 'Chờ duyệt',
            'dang' => 'Đang hiển thị',
            'an' => 'Đã ẩn',
            'tu_choi' => 'Từ chối',
            default => 'Không xác định',
        };
    }

    public function getTrangThaiColorAttribute()
    {
        return match ($this->trang_thai) {
            'dang' => 'bg-green-100 text-green-700',
            'cho_duyet' => 'bg-yellow-100 text-yellow-700',
            'an' => 'bg-gray-200 text-gray-700',
            'tu_choi' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function anhBaiDang()
    {
        return $this->hasMany(AnhBaiDang::class, 'bai_dang_id');
    }

    public function anhDaiDien()
    {
        return $this->hasOne(AnhBaiDang::class, 'bai_dang_id')->orderBy('thu_tu');
    }
    public function yeuCauThue()
    {
        return $this->hasMany(YeuCauThue::class, 'bai_dang_id');
    }
}
