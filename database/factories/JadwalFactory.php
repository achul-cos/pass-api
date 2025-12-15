<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Jadwal>
 */
class JadwalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $faker = \Faker\Factory::create('id_ID');

        // Daftar Nama Kapal
        $kapal = $faker->randomElement([
            'KMP Senangin',
            'KMP Sembilang',
            'KMP Satria Pratama',
            'KMP Citra Mandala Abadi',
            'KMP Tanjung Burang',
            'KMP Niaga Fery II'
        ]);

        // Daftar Lokasi Tujuan
        $daftarTujuan = [
            "Dabo Singkep",
            "Kuala Tungkal",
            "Tanjung Balai Karimun",
            "Bintan",
            "Sei Selari Pakning",
            "Tanjung Uban",
        ];

        // Fungsi Faker untuk mengacak tempat tujuan
        $tujuan = $faker->randomElement($daftarTujuan);

        // Daftar durasi perjalanan berdasarkan rute (dalam jam)
        $durasiPerjalanan = [
            "Dabo Singkep" => [6, 9],
            "Kuala Tungkal" => [8, 12],
            "Tanjung Balai Karimun" => [8, 10],
            "Bintan" => [2, 4],
            "Sei Selari Pakning" => [10, 14],
            "Tanjung Uban" => [3, 7],
        ];

        // Menetukan Waktu Berangkat
        $waktuBerangkat = Carbon::tomorrow()
            ->addHours($faker->numberBetween(6, 20)) // Antara jam 06:00 – 20:00
            ->addMinutes($faker->numberBetween(0, 59));

        // Hitung waktu tiba berdasarkan durasi sesuai tujuan
        $durasiJam = $faker->numberBetween(
            $durasiPerjalanan[$tujuan][0],
            $durasiPerjalanan[$tujuan][1]
        );
        $waktuTiba = (clone $waktuBerangkat)->addHours($durasiJam);

        //Daftar Harga tiket berdasarkan rute (dalam IDR)
        $biayaPerjalanan = [
            "Dabo Singkep" => [120000, 180000],       // harga random 120k–180k
            "Kuala Tungkal" => [200000, 300000],      // harga random 200k–300k
            "Tanjung Balai Karimun" => [100000, 150000],
            "Bintan" => [50000, 80000],
            "Sei Selari Pakning" => [220000, 280000],
            "Tanjung Uban" => [60000, 100000],
        ];

        // ambil harga random sesuai tujuan
        $biayaPerjalanan = $faker->numberBetween($biayaPerjalanan[$tujuan][0], $biayaPerjalanan[$tujuan][1]);

        //----------------------------------------------------------------------

        $biayaPenumpang = [
            "Dabo Singkep" => [20000, 50000],       // harga random 120k–180k
            "Kuala Tungkal" => [30000, 70000],      // harga random 200k–300k
            "Tanjung Balai Karimun" => [50000, 100000],
            "Bintan" => [50000, 80000],
            "Sei Selari Pakning" => [10000, 30000],
            "Tanjung Uban" => [10000, 20000],
        ];

        // ambil harga random sesuai tujuan
        $biayaPenumpang = $faker->numberBetween($biayaPenumpang[$tujuan][0], $biayaPenumpang[$tujuan][1]);

        //----------------------------------------------------------------------

        $biayaMotor = [
            "Dabo Singkep" => [60000, 120000],       // harga random 120k–180k
            "Kuala Tungkal" => [100000, 200000],      // harga random 200k–300k
            "Tanjung Balai Karimun" => [50000, 100000],
            "Bintan" => [50000, 80000],
            "Sei Selari Pakning" => [100000, 200000],
            "Tanjung Uban" => [60000, 100000],
        ];

        // ambil harga random sesuai tujuan
        $biayaMotor = $faker->numberBetween($biayaMotor[$tujuan][0], $biayaMotor[$tujuan][1]);

        //----------------------------------------------------------------------

        $biayaMobil = [
            "Dabo Singkep" => [240000, 360000],       // harga random 120k–180k
            "Kuala Tungkal" => [400000, 600000],      // harga random 200k–300k
            "Tanjung Balai Karimun" => [500000, 700000],
            "Bintan" => [500000, 800000],
            "Sei Selari Pakning" => [600000, 800000],
            "Tanjung Uban" => [600000, 1000000],
        ];

        // ambil harga random sesuai tujuan
        $biayaMobil = $faker->numberBetween($biayaMobil[$tujuan][0], $biayaMobil[$tujuan][1]);

        return [
            'nama_jadwal' => "Pelabuhan Telaga Punggur (Kota Batam) - {$tujuan}",
            'waktu_berangkat' => $waktuBerangkat,
            'waktu_tiba' => $waktuTiba,
            'lokasi_berangkat' => "Pelabuhan Telaga Punggur",
            'lokasi_tiba' => $tujuan,
            'biaya_perjalanan' => $biayaPerjalanan,
            'biaya_penumpang' => $biayaPenumpang,
            'biaya_motor' => $biayaMotor,
            'biaya_mobil' => $biayaMobil,
            'pajak' => 11.0,
            'diskon' => 0,
            'kapasitas' => $faker->numberBetween(25, 50) * 2,
            'nama_kapal' => $kapal,
        ];
    }
}
