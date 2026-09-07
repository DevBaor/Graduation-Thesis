<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chi_so', function (Blueprint $table) {
            if (!Schema::hasColumn('chi_so', 'nguoi_sua_id')) {
                $table->unsignedInteger('nguoi_sua_id')->nullable()->after('nguoi_nhap_id');
            }

            if (!Schema::hasColumn('chi_so', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('ghi_chu');
            }
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chi_so', function (Blueprint $table) {
            $table->dropForeign(['nguoi_sua_id']);
            $table->dropColumn(['nguoi_sua_id', 'updated_at']);
        });
    }
};
