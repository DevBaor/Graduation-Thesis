<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncRoomStatus extends Command
{
    /**
     * Tên lệnh chạy trong Artisan
     *
     * @var string
     */
    protected $signature = 'sync:room-status';

    /**
     * Mô tả chức năng
     *
     * @var string
     */
    protected $description = 'Đồng bộ trạng thái phòng dựa trên hợp đồng đang hiệu lực và trạng thái bảo trì';

    /**
     * Thực thi lệnh
     */
    public function handle()
    {
        $this->info('🔄 Bắt đầu đồng bộ trạng thái phòng...');

        // Các phòng có hợp đồng đang hiệu lực → “đã thuê”
        $activeContracts = DB::table('hop_dong')
            ->where('trang_thai', 'hieu_luc')
            ->pluck('phong_id')
            ->unique()
            ->toArray();

        if (!empty($activeContracts)) {
            DB::table('phong')
                ->whereIn('id', $activeContracts)
                ->where('trang_thai', '!=', 'bao_tri')
                ->update(['trang_thai' => 'da_thue']);
            $this->info('✅ Cập nhật ' . count($activeContracts) . ' phòng thành "đã thuê"');
        }

        // 2 Phòng không có hợp đồng hiệu lực → “trống” (trừ khi bảo trì)
        $roomsWithContracts = DB::table('hop_dong')
            ->where('trang_thai', 'hieu_luc')
            ->pluck('phong_id')
            ->unique()
            ->toArray();

        DB::table('phong')
            ->whereNotIn('id', $roomsWithContracts)
            ->where('trang_thai', '!=', 'bao_tri')
            ->update(['trang_thai' => 'trong']);

        $this->info('✅ Đặt các phòng còn lại (không có hợp đồng hiệu lực) về "trống"');

        // Kiểm tra các phòng bảo trì — không được có hợp đồng hiệu lực
        $baoTriRooms = DB::table('phong')
            ->where('trang_thai', 'bao_tri')
            ->pluck('id')
            ->toArray();

        if (!empty($baoTriRooms)) {
            $affected = DB::table('hop_dong')
                ->whereIn('phong_id', $baoTriRooms)
                ->where('trang_thai', 'hieu_luc')
                ->update(['trang_thai' => 'huy']);

            if ($affected > 0) {
                $this->warn("⚠️ Đã hủy $affected hợp đồng vì phòng đang bảo trì!");
            }
        }

        $this->info('🎉 Đồng bộ hoàn tất!');
    }
}
