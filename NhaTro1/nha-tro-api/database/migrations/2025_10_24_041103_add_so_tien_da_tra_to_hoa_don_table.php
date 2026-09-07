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
        Schema::table('hoa_don', function (Blueprint $table) {
            $table->decimal('so_tien_da_tra', 12, 2)->default(0);
            $table->boolean('da_gui_canh_bao')->default(false);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hoa_don', function (Blueprint $table) {
            //
        });
    }
};
