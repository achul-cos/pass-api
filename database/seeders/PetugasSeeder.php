<?php

namespace Database\Seeders;

use App\Models\Petugas;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PetugasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $petugasTesting = Petugas::create([
            'nama' => 'PetugasTesting',
            'password' => bcrypt('password'),
        ]);
    }
}
