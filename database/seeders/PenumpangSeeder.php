<?php

namespace Database\Seeders;

use App\Models\Penumpang;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PenumpangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat Akun Penumpang Testing
        $penumpangTesting = Penumpang::factory()->create([
            'nama' => 'PenumpangTesting',
            'nomor_telepon' => '089668914466',
            'password' => bcrypt('password'),
        ]);

        // Buat Akun Dummy Menggunakan Factory
        $penumpangDummy = Penumpang::factory(30)->create();

        // Gabungkan Akun Testing Dengan Akun Dummy
        $penumpangDummy->push($penumpangTesting);
    }
}
