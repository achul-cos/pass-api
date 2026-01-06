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
        Schema::create('kendaraans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tikets')->onDelete('cascade'); // foreign key ke tabel tikets
            $table->foreignId('penumpang_id')->constrained('penumpangs')->onDelete('cascade'); // foreign key ke tabel tikets
            $table->foreignId('jadwal_id')->constrained('jadwals')->onDelete('cascade'); // foreign key ke tabel jadwals
            $table->foreignId('parkir1_id')->constrained('parkirs')->onDelete('cascade')->nullable(); // foreign key ke tabel parkirs
            $table->foreignId('parkir2_id')->constrained('parkirs')->onDelete('cascade')->nullable(); // foreign key ke tabel parkirs
            $table->dateTime('waktu_check_in');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kendaraans');
    }
};
