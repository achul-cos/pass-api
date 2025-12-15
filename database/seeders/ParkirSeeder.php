<?php

namespace Database\Seeders;

use App\Models\Parkir;
use Illuminate\Database\Seeder;

class ParkirSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kodeParkir = range('A', 'R');
        $lebar = 3; // jumlah kolom per baris

        foreach ($kodeParkir as $index => $kode) {
            // index dimulai dari 0, jadi A = 0, B = 1, ..., R = 17

            // Kolom: 1, 2, 3, 1, 2, 3, ... (sisa bagi 3, lalu + 1)
            $kolom = ($index % $lebar) + 1;

            // Baris: 1, 1, 1, 2, 2, 2, 3, 3, 3, ... (hasil bagi 3, lalu + 1)
            $baris = intdiv($index, $lebar) + 1;

            Parkir::create([
                'kode_parkir' => $kode,
                'kolom'       => $kolom,
                'baris'       => $baris,
            ]);
        }
    }
}
