<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Xóa trigger cũ nếu có
        DB::unprepared('DROP TRIGGER IF EXISTS trg_hopdong_insert;');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_hopdong_update;');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_hopdong_delete;');

        // 🔹 Khi thêm hợp đồng → phòng chuyển sang “đã thuê”
        DB::unprepared('
            CREATE TRIGGER trg_hopdong_insert
            AFTER INSERT ON hop_dong
            FOR EACH ROW
            BEGIN
                UPDATE phong
                SET trang_thai = "da_thue",
                    ngay_cap_nhat = NOW()
                WHERE id = NEW.phong_id;
            END
        ');

        // 🔹 Khi cập nhật hợp đồng
        DB::unprepared('
            CREATE TRIGGER trg_hopdong_update
            AFTER UPDATE ON hop_dong
            FOR EACH ROW
            BEGIN
                IF NEW.trang_thai = "ket_thuc" OR NEW.trang_thai = "huy" THEN
                    UPDATE phong
                    SET trang_thai = "trong",
                        ngay_cap_nhat = NOW()
                    WHERE id = NEW.phong_id;
                ELSEIF NEW.trang_thai = "hieu_luc" THEN
                    UPDATE phong
                    SET trang_thai = "da_thue",
                        ngay_cap_nhat = NOW()
                    WHERE id = NEW.phong_id;
                END IF;
            END
        ');

        // 🔹 Khi xóa hợp đồng → phòng chuyển “trống”
        DB::unprepared('
            CREATE TRIGGER trg_hopdong_delete
            AFTER DELETE ON hop_dong
            FOR EACH ROW
            BEGIN
                UPDATE phong
                SET trang_thai = "trong",
                    ngay_cap_nhat = NOW()
                WHERE id = OLD.phong_id;
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_hopdong_insert;');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_hopdong_update;');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_hopdong_delete;');
    }
};
