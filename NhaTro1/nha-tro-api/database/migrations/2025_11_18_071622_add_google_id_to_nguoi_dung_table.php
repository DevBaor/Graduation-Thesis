<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nguoi_dung', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('anh_dai_dien');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nguoi_dung', function (Blueprint $table) {
            //
        });
    }
};
