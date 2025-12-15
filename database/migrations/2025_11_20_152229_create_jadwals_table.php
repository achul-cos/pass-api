<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jadwals', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jadwal');
            $table->dateTime('waktu_berangkat');
            $table->dateTime('waktu_tiba');
            $table->string('lokasi_berangkat');
            $table->string('lokasi_tiba');
            $table->integer('biaya_perjalanan');
            $table->integer('biaya_penumpang');
            $table->integer('biaya_motor');
            $table->integer('biaya_mobil');
            $table->integer('diskon')->nullable();
            $table->float('pajak')->nullable();
            $table->integer('kapasitas');
            $table->string('nama_kapal');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwals');
    }
};
