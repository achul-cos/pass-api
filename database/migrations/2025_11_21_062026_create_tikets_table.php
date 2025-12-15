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
        Schema::create('tikets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penumpang_id')->constrained('penumpangs')->onDelete('cascade'); // foreign key ke tabel penumpangs
            $table->foreignId('jadwal_id')->constrained('jadwals')->onDelete('cascade'); // foreign key ke tabel jadwals
            $table->enum('status', ['menunggu_verifikasi', 'terverifikasi', 'dibatalkan'])->default('menunggu_verifikasi'); // status tiket
            $table->json('penumpang_list'); // simpan array nama penumpang
            $table->string('nomor_kendaraan'); // Plat kendaraan yang digunakan
            $table->enum('jenis_kendaraan', ['mobil', 'motor']); // Jenis kendaraan
            $table->string('kode_unik')->unique()->nullable(); // Kode unik tiket untuk QR Code
            $table->integer('biaya_tiket'); // Biaya tiket
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tikets');
    }
};
