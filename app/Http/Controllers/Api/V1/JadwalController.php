<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Jadwal\UpdateJadwalRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Api\V1\Jadwal\StoreJadwalRequest;
use App\Http\Resources\Api\V1\Jadwal\JadwalResource;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Jadwal;
use Throwable;

class JadwalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $jadwal = Jadwal::all()->map(function ($jadwals) {
        //     return [
                // 'id' => $jadwals->id,
                // 'namaJadwal' => $jadwals->nama_jadwal,
                // 'waktuBerangkat' => $jadwals->waktu_berangkat,
                // 'waktuTiba' => $jadwals->waktu_tiba,
                // 'lokasiBerangkat' => $jadwals->lokasi_berangkat,
                // 'lokasiTiba' => $jadwals->lokasi_tiba,
                // 'biayaPerjalanan' => $jadwals->biaya_perjalanan,
                // 'biayaPenumpang' => $jadwals->biaya_penumpang,
                // 'biayaMotor' => $jadwals->biaya_motor,
                // 'biayaMobil' => $jadwals->biaya_mobil,
                // 'diskon' => $jadwals->diskon,
                // 'pajak' => $jadwals->pajak,
                // 'kapasitas' => $jadwals->kapasitas,
                // 'namaKapal' => $jadwals->nama_kapal,
        //     ];
        // });

        // return response()->json([
        //     'data' => $jadwal
        // ]);

        $jadwal = Jadwal::paginate(5);

        return JadwalResource::collection($jadwal);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJadwalRequest $request)
    {
        // Error Handling try
        try {

            // Menerapkan Aturan Validasi dan Pesan Validasi Gagal Pada Data Input
            $data = $request->validated();

            // Create Data Berdasarkan Data Yang Diinput
            $jadwal = Jadwal::create([
                'nama_jadwal' => $data['namaJadwal'],
                'waktu_berangkat' => $data['waktuBerangkat'],
                'waktu_tiba' => $data['waktuTiba'],
                'lokasi_berangkat' => $data['lokasiBerangkat'],
                'lokasi_tiba' => $data['lokasiTiba'],
                'biaya_perjalanan' => $data['biayaPerjalanan'],
                'biaya_penumpang' => $data['biayaPenumpang'],
                'biaya_motor' => $data['biayaMotor'],
                'biaya_mobil' => $data['biayaMobil'],
                'diskon' => $data['diskon'] ?? null,
                'pajak' => $data['pajak'] ?? 11.0,
                'kapasitas' => $data['kapasitas'],
                'nama_kapal' => $data['namaKapal'],
            ]);

            $data['id'] = $jadwal->id;

            // Memberi Balasan API
            return response()->json([
                'status' => 'success',
                'message' => 'Data Jadwal Telah Tersimpan',
                'data' => $data
            ])->setStatusCode(201);

            // Error Handling catch untuk menangkap error database atau server
        } catch (Throwable $e) {

            // Mengambil pesan error pada variable $errorMessage
            $errorMessage = $e->getMessage();

            // Menulis Log error pada laravel berdasarkan pesan error pada variable
            Log::error($errorMessage);

            // Isi dari balasan API
            $response = [
                'status' => 'error',
                'message' => $errorMessage,
                'data' => $request->all()
            ];

            // balasan API Berupa JSOn
            return response()->json($response);

            // Error Handling catch untuk menangkap error validasi (sebenarnya veri validnya ada di boostrap/app.php pada bagian expection ValidValidation)
        } catch (ValidationException $e) {
            $errorMessage = $e->getMessage();
            $errors = $e->errors();

            log::error($errorMessage);

            $response = [
                'status' => 'error',
                'message' => $errorMessage,
                'errors' => $errors,
                'data' => $request->all()
            ];

            return response()->json($response);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $jadwal = Jadwal::findOrFail($id);

            $data = [
                'id' => $jadwal->id,
                'namaJadwal' => $jadwal->nama_jadwal,
                'waktuBerangkat' => $jadwal->waktu_berangkat,
                'waktuTiba' => $jadwal->waktu_tiba,
                'lokasiBerangkat' => $jadwal->lokasi_berangkat,
                'lokasiTiba' => $jadwal->lokasi_tiba,
                'biayaPerjalanan' => $jadwal->biaya_perjalanan,
                'biayaPenumpang' => $jadwal->biaya_penumpang,
                'biayaMotor' => $jadwal->biaya_motor,
                'biayaMobil' => $jadwal->biaya_mobil,
                'diskon' => $jadwal->diskon,
                'pajak' => $jadwal->pajak,
                'kapasitas' => $jadwal->kapasitas,
                'namaKapal' => $jadwal->nama_kapal,
            ];

            return response()->json([
                'data' => $data
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan atau telah dihapus',
                'request' => $id
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => [
                    'id' => $id
                ]
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateJadwalRequest $request, string $id)
    {

        $data = $request->validated();

        $findJadwal = Jadwal::findOrFail($id);

        $jadwal = [
            'id' => $findJadwal->id,
            'namaJadwal' => $findJadwal->nama_jadwal,
            'waktuBerangkat' => $findJadwal->waktu_berangkat,
            'waktuTiba' => $findJadwal->waktu_tiba,
            'lokasiBerangkat' => $findJadwal->lokasi_berangkat,
            'lokasiTiba' => $findJadwal->lokasi_tiba,
            'biayaPerjalanan' => $findJadwal->biaya_perjalanan,
            'biayaPenumpang' => $findJadwal->biaya_penumpang,
            'biayaMotor' => $findJadwal->biaya_motor,
            'biayaMobil' => $findJadwal->biaya_mobil,
            'diskon' => $findJadwal->diskon,
            'pajak' => $findJadwal->pajak,
            'kapasitas' => $findJadwal->kapasitas,
            'namaKapal' => $findJadwal->nama_kapal,
        ];

        try {
            // Update Data Berdasarkan Data Yang Diinput
            $findJadwal->update([
                'nama_jadwal' => $data['namaJadwal']            ?? $findJadwal['nama_jadwal'],
                'waktu_berangkat' => $data['waktuBerangkat']    ?? $findJadwal['waktu_berangkat'],
                'waktu_tiba' => $data['waktuTiba']              ?? $findJadwal['waktu_tiba'],
                'lokasi_berangkat' => $data['lokasiBerangkat']  ?? $findJadwal['lokasi_berangkat'],
                'lokasi_tiba' => $data['lokasiTiba']            ?? $findJadwal['lokasi_tiba'],
                'biaya_perjalanan' => $data['biayaPerjalanan']  ?? $findJadwal['biaya_perjalanan'],
                'biaya_penumpang' => $data['biayaPenumpang']    ?? $findJadwal['biaya_penumpang'],
                'biaya_motor' => $data['biayaMotor']            ?? $findJadwal['biaya_motor'],
                'biaya_mobil' => $data['biayaMobil']            ?? $findJadwal['biaya_mobil'],
                'diskon' => $data['diskon']                     ?? $findJadwal['diskon'],
                'pajak' => $data['pajak']                       ?? $findJadwal['pajak'],
                'kapasitas' => $data['kapasitas']               ?? $findJadwal['kapasitas'],
                'nama_kapal' => $data['namaKapal']              ?? $findJadwal['nama_kapal'],
            ]);

            $jadwal = [
                'id' => $findJadwal->id,
                'namaJadwal' => $findJadwal->nama_jadwal,
                'waktuBerangkat' => $findJadwal->waktu_berangkat,
                'waktuTiba' => $findJadwal->waktu_tiba,
                'lokasiBerangkat' => $findJadwal->lokasi_berangkat,
                'lokasiTiba' => $findJadwal->lokasi_tiba,
                'biayaPerjalanan' => $findJadwal->biaya_perjalanan,
                'biayaPenumpang' => $findJadwal->biaya_penumpang,
                'biayaMotor' => $findJadwal->biaya_motor,
                'biayaMobil' => $findJadwal->biaya_mobil,
                'diskon' => $findJadwal->diskon,
                'pajak' => $findJadwal->pajak,
                'kapasitas' => $findJadwal->kapasitas,
                'namaKapal' => $findJadwal->nama_kapal,
            ];

            // Memberi Balasan API
            return response()->json([
                'status' => 'success',
                'message' => 'Data ' . $id . ' Telah Terupdate',
                'request' => $data,
                'data' => $jadwal
            ]);
        } catch (Throwable $e) {
            // Mengambil pesan error pada variable $errorMessage
            $errorMessage = $e->getMessage();

            // Menulis Log error pada laravel berdasarkan pesan error pada variable
            Log::error($errorMessage);

            // Isi dari balasan API
            $response = [
                'status' => 'error',
                'message' => $errorMessage,
                'request' => $data,
                'data' => $jadwal
            ];

            // balasan API Berupa JSON
            return response()->json($response);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $findJadwal = Jadwal::findOrFail($id);

            $data = [
                'id' => $findJadwal->id,
                'namaJadwal' => $findJadwal->nama_jadwal,
                'waktuBerangkat' => $findJadwal->waktu_berangkat,
                'waktuTiba' => $findJadwal->waktu_tiba,
                'lokasiBerangkat' => $findJadwal->lokasi_berangkat,
                'lokasiTiba' => $findJadwal->lokasi_tiba,
                'biayaPerjalanan' => $findJadwal->biaya_perjalanan,
                'biayaPenumpang' => $findJadwal->biaya_penumpang,
                'biayaMotor' => $findJadwal->biaya_motor,
                'biayaMobil' => $findJadwal->biaya_mobil,
                'diskon' => $findJadwal->diskon,
                'pajak' => $findJadwal->pajak,
                'kapasitas' => $findJadwal->kapasitas,
                'namaKapal' => $findJadwal->nama_kapal,
            ];

            $findJadwal->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data ' . $id . ' Telah Dihapus',
                'request' => $id,
                'data' => $data,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan atau telah dihapus'
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Restored the specified resource from storage.
     */
    public function restore(string $id)
    {
        try {
            $jadwal = Jadwal::onlyTrashed()->findOrFail($id);

            $jadwal->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Data ' . $id . ' Telah Dipulihkan',
                'data' => [
                    'id'             => $jadwal->id,
                    'namaJadwal'     => $jadwal->nama_jadwal,
                    'waktuBerangkat' => $jadwal->waktu_berangkat,
                    'waktuTiba'      => $jadwal->waktu_tiba,
                    'lokasiBerangkat' => $jadwal->lokasi_berangkat,
                    'lokasiTiba'     => $jadwal->lokasi_tiba,
                    'biayaPerjalanan' => $jadwal->biaya_perjalanan,
                    'biayaPenumpang' => $jadwal->biaya_penumpang,
                    'biayaMotor' => $jadwal->biaya_motor,
                    'biayaMobil' => $jadwal->biaya_mobil,
                    'diskon' => $jadwal->diskon,
                    'pajak' => $jadwal->pajak,
                    'kapasitas' => $jadwal->kapasitas,
                    'namaKapal' => $jadwal->nama_kapal,
                ]
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak dapat dipulihkan, dikarenakan telah dihapus permanen atau tidak pernah ada',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
