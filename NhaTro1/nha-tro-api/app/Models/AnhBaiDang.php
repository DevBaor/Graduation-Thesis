<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnhBaiDang extends Model
{
    use HasFactory;

    protected $table = 'anh_bai_dang';
    public $timestamps = false;

    protected $fillable = [
        'bai_dang_id',
        'url',
        'thu_tu',
        'ngay_tao',
    ];

    protected $casts = [
        'ngay_tao' => 'datetime',
    ];

    public function baiDang()
    {
        return $this->belongsTo(BaiDang::class, 'bai_dang_id');
    }

    public function getUrlAttribute($value)
    {
        if (!$value)
            return null;

        // Bỏ prefix sai nếu có
        $clean = str_replace(['public/', 'storage/'], '', $value);

        // Lấy host API từ .env (fallback nếu chưa có)
        $apiBase = rtrim(env('API_BASE_URL', 'http://127.0.0.1:8000'), '/');

        // Trả về link ảnh đầy đủ
        return "{$apiBase}/storage/" . ltrim($clean, '/');
    }


}
