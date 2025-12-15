<?php

namespace Database\Seeders;

use App\Models\Hardware;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HardwareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $welcomingGateway = Hardware::create([
            'name' => 'Welcoming Gateway',
            'desc' => 'Alat Scan Tiket dan Kendaraan di gerbang selamat datang',
            'status' => 'offline',
        ]);

        $holdingGateway = Hardware::create([
            'name' => 'Holding Gateway',
            'desc' => 'Alat checkout di gerbang selamat datang',
            'status' => 'offline',
        ]);
    }
}
