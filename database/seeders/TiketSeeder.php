<?php

namespace Database\Seeders;

use App\Models\Tiket;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use PHPUnit\Framework\Attributes\Ticket;

class TiketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ticket Seeder Membuat Ticket tentunya

        Tiket::factory()->count(20)->create();
    }
}
