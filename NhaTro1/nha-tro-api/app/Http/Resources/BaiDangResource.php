<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BaiDangResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tieu_de' => $this->tieu_de,
            'mo_ta' => $this->mo_ta,
            'gia_niem_yet' => $this->gia_niem_yet,
            'trang_thai' => $this->trang_thai,
            'phong' => [
                'id' => $this->phong->id,
                'so_phong' => $this->phong->so_phong,
                'gia' => $this->phong->gia,
                'tien_ich' => $this->phong->tienIch->pluck('ten'),
                'day_tro' => [
                    'id' => $this->phong->dayTro->id ?? null,
                    'ten' => $this->phong->dayTro->ten_day_tro ?? null,
                    'dia_chi' => $this->phong->dayTro->dia_chi ?? null,
                ]
            ],
            'anh' => $this->anh->map(fn($a) => ['url' => $a->url, 'thu_tu' => $a->thu_tu]),
            'nguoi_dang' => [
                'id' => $this->nguoiDung->id ?? null,
                'ho_ten' => $this->nguoiDung->ho_ten ?? null,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
