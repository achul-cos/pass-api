<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kendaraan;
use Throwable;

class KendaraanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $kendaraans = Kendaraan::with(['tiket', 'penumpang', 'jadwal', 'parkir'])->get();

            if ($kendaraans->isEmpty()) {

                $data = [];

                return response()->json([
                    'status' => 'error',
                    'data' => $data,
                    'message' => 'Data Kendaraan Tidak Ditemukan Atau Tidak Ada',
                ]);
            } else {
                $data = $kendaraans->map(function ($kendaraan) {
                    return [
                        'id' => $kendaraan->id,
                        'tiket_id' => $kendaraan->tiket_id,
                        'penumpang_id' => $kendaraan->penumpang_id,
                        'jadwal_id' => $kendaraan->jadwal_id,
                        'parkir_id' => $kendaraan->parkir_id,
                        'waktu_check_in' => $kendaraan->waktu_check_in,

                        // Data Dari Tabel Tiket
                        'tiket' => $kendaraan->tiket ? [
                            'id' => $kendaraan->tiket->id,
                            'penumpang_id' => $kendaraan->tiket->penumpang_id,
                            'jadwal_id' => $kendaraan->tiket->jadwal_id,
                            'penumpang_list' => json_decode($kendaraan->tiket->penumpang_list),
                            'nomor_kendaraan' => $kendaraan->tiket->nomor_kendaraan,
                            'jenis_kendaraan' => $kendaraan->tiket->jenis_kendaraan,
                            'kode_unik' => $kendaraan->tiket->kode_unik,
                            'biaya_tiket' => $kendaraan->tiket->biaya_tiket,
                        ] : null,

                        // Data Dari Tabel Penumpang
                        'penumpang' => $kendaraan->penumpang ? [
                            'id' => $kendaraan->penumpang->id,
                            'nama' => $kendaraan->penumpang->nama,
                            'nomorTelepon' => $kendaraan->penumpang->nomor_telepon,
                        ] : null,

                        // Data Dari Tabel Jadwal
                        'jadwal' => $kendaraan->jadwal ? [
                            'id' => $kendaraan->jadwal->id,
                            'namaJadwal' => $kendaraan->jadwal->nama_jadwal,
                            'waktuBerangkat' => $kendaraan->jadwal->waktu_berangkat,
                            'waktuTiba' => $kendaraan->jadwal->waktu_tiba,
                            'lokasiBerangkat' => $kendaraan->jadwal->lokasi_berangkat,
                            'lokasiTiba' => $kendaraan->jadwal->lokasi_tiba,
                            'biayaPerjalanan' => $kendaraan->jadwal->biaya_perjalanan,
                            'biayaPenumpang' => $kendaraan->jadwal->biaya_penumpang,
                            'biayaMotor' => $kendaraan->jadwal->biaya_motor,
                            'biayaMobil' => $kendaraan->jadwal->biaya_mobil,
                            'pajak' => $kendaraan->jadwal->pajak,
                            'diskon' => $kendaraan->jadwal->diskon,
                            'kapasitas' => $kendaraan->jadwal->kapasitas,
                            'namaKapal' => $kendaraan->jadwal->nama_kapal,
                        ] : null,

                        // Data Dari Tabel Parkir
                        'parkir' => $kendaraan->parkir ? [
                            'id' => $kendaraan->parkir->id,
                            'kodeParkir' => $kendaraan->parkir->kode_parkir,
                            'kolom' => $kendaraan->parkir->kolom,
                            'baris' => $kendaraan->parkir->baris,
                        ] : null,
                    ];
                });

                return response()->json([
                    'status' => 'success',
                    'message' => 'Data Kendaraan Ditemukan',
                    'data' => $data,
                ]);
            }
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
