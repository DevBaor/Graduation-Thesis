<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HoaDon;
use App\Services\HoaDonService;

class FixHoaDonTongTien extends Command
{
    /**
     * Tên lệnh chạy trong terminal
     *
     * @var string
     */
    protected $signature = 'hoadon:fix-tong-tien';

    /**
     * Mô tả của lệnh
     *
     * @var string
     */
    protected $description = 'Cập nhật lại tổng tiền (dịch vụ, điện nước, tổng cộng) cho tất cả hóa đơn';

    /**
     * Thực thi lệnh
     */
    public function handle()
    {
        $service = new HoaDonService();
        $hoaDons = HoaDon::all();
        $this->info('🔄 Đang cập nhật lại tổng tiền hóa đơn...');

        $count = 0;
        foreach ($hoaDons as $hoaDon) {
            $service->capNhatTongTienHoaDon($hoaDon);
            $count++;
            $this->line("✅ Hóa đơn ID {$hoaDon->id} cập nhật xong.");
        }

        $this->info("🎯 Hoàn tất! Đã cập nhật {$count} hóa đơn.");
        return Command::SUCCESS;
    }
}
