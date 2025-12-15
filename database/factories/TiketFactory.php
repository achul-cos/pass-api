<?php

namespace Database\Factories;

use App\Models\Jadwal;
use App\Models\Penumpang;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

use Faker\Factory as Faker;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tiket>
 */
class TiketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = Faker::create('id_ID');

        $jenisKendaraan = $faker->randomElement(['mobil', 'motor']);

        if ($jenisKendaraan === 'motor') {
            $jumlahPenumpang = $faker->numberBetween(1, 2);
        } else {
            $jumlahPenumpang = $faker->numberBetween(1, 6);
        }

        $jadwalRandom = Jadwal::inRandomOrder()->first();

        $biayaPerjalanan = $jadwalRandom->biaya_perjalanan;

        $biayaPenumpang = $jadwalRandom->biaya_penumpang;

        if ($jenisKendaraan === 'motor') {
            $biayaKendaraan = $jadwalRandom->biaya_motor;
        } else {
            $biayaKendaraan = $jadwalRandom->biaya_mobil;
        }

        $listPenumpang =
            json_encode(
                collect(
                    range(1, $jumlahPenumpang)
                )->map(
                    fn() => $faker->name()
                )->toArray()
            );

        $pajak = $jadwalRandom->pajak;

        $diskon = $jadwalRandom->diskon;

        $biayaKotor = (($biayaPerjalanan) + ($biayaKendaraan) + ($biayaPenumpang * $jumlahPenumpang) - ($diskon));

        $biayaBersih = $biayaKotor + ($biayaKotor * ($pajak / 100));

        return [
            'penumpang_id' => Penumpang::inRandomOrder()->first()->id,
            'jadwal_id' => $jadwalRandom->id,
            'nomor_kendaraan' => $faker->regexify('[A-Z]{1,2} [0-9]{3,4} [A-Z]{1,3}'),
            'jenis_kendaraan' => $jenisKendaraan,
            'penumpang_list' => $listPenumpang,
            'status' => $faker->randomElement(['menunggu_verifikasi', 'terverifikasi', 'dibatalkan']),
            'biaya_tiket' => $biayaBersih,
            'kode_unik' => Str::uuid(),
        ];
    }
}
