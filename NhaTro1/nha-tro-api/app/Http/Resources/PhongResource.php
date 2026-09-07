<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PhongResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'so_phong' => $this->so_phong,
            'gia' => $this->gia,
            'trang_thai' => $this->trang_thai,
            'dien_tich' => $this->dien_tich,
            'tang' => $this->tang,
            'day_tro' => [
                'id' => $this->dayTro->id ?? null,
                'ten' => $this->dayTro->ten_day_tro ?? null,
                'dia_chi' => $this->dayTro->dia_chi ?? null,
            ],
            'tien_ich' => $this->whenLoaded('tienIch', fn() => $this->tienIch->map(fn($t) => [
                'id' => $t->id,
                'ten' => $t->ten,
                'icon' => $t->icon
            ])),
            'bai_dang_moi_nhat' => $this->whenLoaded('baiDang', fn() => optional($this->baiDang->first(), function ($b) {
                return ['id' => $b->id, 'tieu_de' => $b->tieu_de, 'gia_niem_yet' => $b->gia_niem_yet];
            })),
            'created_at' => $this->created_at,
        ];
    }
}
