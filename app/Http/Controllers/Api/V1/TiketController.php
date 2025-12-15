<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Api\V1\Tiket\StoreTiketRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Jadwal\UpdateJadwalRequest;
use App\Http\Requests\Api\V1\Tiket\ValidateTiketRequest;
use App\Http\Requests\Api\V1\Tiket\ValidateTiketWithOutNomorKendaraanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Jadwal;
use App\Models\Tiket;
use Throwable;
use Carbon\Carbon;

class TiketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $tikets = Tiket::with(['penumpang', 'jadwal'])->get();

            $data = $tikets->map(function ($tiket) {
                return [
                    'id' => $tiket->id,
                    'penumpangId' => $tiket->penumpang_id,
                    'jadwalId' => $tiket->jadwal_id,
                    'status' => $tiket->status,
                    'penumpangList' => json_decode($tiket->penumpang_list),
                    'nomorKendaraan' => $tiket->nomor_kendaraan,
                    'jenisKendaraan' => $tiket->jenis_kendaraan,
                    'kodeUnik' => $tiket->kode_unik,
                    'biaya_tiket' => $tiket->biaya_tiket,

                    // Data Dari Tabel Penumpang
                    'penumpang' => $tiket->penumpang ? [
                        'id' => $tiket->penumpang->id,
                        'nama' => $tiket->penumpang->nama,
                        'nomorTelepon' => $tiket->penumpang->nomor_telepon,
                    ] : null,

                    // Data Dari Tabel Jadwal
                    'jadwal' => $tiket->jadwal ? [
                        'id' => $tiket->jadwal->id,
                        'namaJadwal' => $tiket->jadwal->nama_jadwal,
                        'waktuBerangkat' => $tiket->jadwal->waktu_berangkat,
                        'waktuTiba' => $tiket->jadwal->waktu_tiba,
                        'lokasiBerangkat' => $tiket->jadwal->lokasi_berangkat,
                        'lokasiTiba' => $tiket->jadwal->lokasi_tiba,
                        'biayaPerjalanan' => $tiket->jadwal->biaya_perjalanan,
                        'biayaPenumpang' => $tiket->jadwal->biaya_penumpang,
                        'biayaMotor' => $tiket->jadwal->biaya_motor,
                        'biayaMobil' => $tiket->jadwal->biaya_mobil,
                        'pajak' => $tiket->jadwal->pajak,
                        'diskon' => $tiket->jadwal->diskon,
                        'kapasitas' => $tiket->jadwal->kapasitas,
                        'namaKapal' => $tiket->jadwal->nama_kapal,
                    ] : null,
                ];
            });

            // Berikan response JSON
            return response()->json([
                'data' => $data,
            ]);
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
    public function store(StoreTiketRequest $request)
    {
        try {
            // Validasi Data Request Menggunakan StoreTiketRequest
            $data = $request->validated();

            // Mengambil data jadwal berdasarkan jadwalId yang direquest
            $jadwal = Jadwal::find($data['jadwalId']);

            // Mencari jumlah penumpang berdasarkan penumpangList yang direquest
            $jumlahPenumpang = count($data['penumpangList']);

            // Menentukan biaya kendaraan berdasarkan jenis kendaraan
            if ($data['jenisKendaraan'] === 'motor') {
                $biayaKendaraan = $jadwal->biaya_motor;
            } elseif ($data['jenisKendaraan'] === 'mobil') {
                $biayaKendaraan = $jadwal->biaya_mobil;
            }

            // Ambil komponen biaya dari jadwal
            $biayaPerjalanan = $jadwal->biaya_perjalanan;
            $biayaPenumpang  = $jadwal->biaya_penumpang;
            $diskon          = $jadwal->diskon ?? 0;
            $pajak           = $jadwal->pajak ?? 11;

            // Hitung biaya kotor
            $biayaKotor = (($biayaPerjalanan) + ($biayaKendaraan) + ($biayaPenumpang * $jumlahPenumpang) - ($diskon));

            // Hitung biaya bersih
            $biayaBersih = (int)($biayaKotor + ($biayaKotor * ($pajak / 100)));

            // Membuat Data Tiket Baru
            $tiket = Tiket::create([
                'penumpang_id' => $data['penumpangId'],
                'jadwal_id' => $data['jadwalId'],
                'penumpang_list' => json_encode($data['penumpangList']),
                'nomor_kendaraan' => $data['nomorKendaraan'],
                'jenis_kendaraan' => $data['jenisKendaraan'],
                'kode_unik' => Str::uuid(),
                'biaya_tiket' => $biayaBersih,
            ]);

            // Load relasi
            $tiket->load(['penumpang', 'jadwal']);

            // Membuat response data
            $responseData = [
                'id' => $tiket->id,
                'penumpang_id' => $tiket->penumpang_id,
                'jadwal_id' => $tiket->jadwal_id,
                'penumpang_list' => json_decode($tiket->penumpang_list),
                'nomor_kendaraan' => $tiket->nomor_kendaraan,
                'jenis_kendaraan' => $tiket->jenis_kendaraan,
                'kode_unik' => $tiket->kode_unik,
                'biaya_tiket' => $tiket->biaya_tiket,

                // Data Dari Tabel Penumpang
                'penumpang' => $tiket->penumpang ? [
                    'id' => $tiket->penumpang->id,
                    'nama' => $tiket->penumpang->nama,
                    'nomorTelepon' => $tiket->penumpang->nomor_telepon,
                ] : null,

                // Data Dari Tabel Jadwal
                'jadwal' => $tiket->jadwal ? [
                    'id' => $tiket->jadwal->id,
                    'namaJadwal' => $tiket->jadwal->nama_jadwal,
                    'waktuBerangkat' => $tiket->jadwal->waktu_berangkat,
                    'waktuTiba' => $tiket->jadwal->waktu_tiba,
                    'lokasiBerangkat' => $tiket->jadwal->lokasi_berangkat,
                    'lokasiTiba' => $tiket->jadwal->lokasi_tiba,
                    'biayaPerjalanan' => $tiket->jadwal->biaya_perjalanan,
                    'biayaPenumpang' => $tiket->jadwal->biaya_penumpang,
                    'biayaMotor' => $tiket->jadwal->biaya_motor,
                    'biayaMobil' => $tiket->jadwal->biaya_mobil,
                    'pajak' => $tiket->jadwal->pajak,
                    'diskon' => $tiket->jadwal->diskon,
                    'kapasitas' => $tiket->jadwal->kapasitas,
                    'namaKapal' => $tiket->jadwal->nama_kapal,
                ] : null,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Tiket berhasil dibuat',
                'data' => $responseData,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $tiket = Tiket::with(['penumpang', 'jadwal'])->findOrFail($id);

            $data = [
                'id' => $tiket->id,
                'penumpangId' => $tiket->penumpang_id,
                'jadwalId' => $tiket->jadwal_id,
                'status' => $tiket->status,
                'penumpangList' => json_decode($tiket->penumpang_list),
                'nomorKendaraan' => $tiket->nomor_kendaraan,
                'jenisKendaraan' => $tiket->jenis_kendaraan,
                'kodeUnik' => $tiket->kode_unik,
                'biayaTiket' => $tiket->biaya_tiket,

                // Data Dari Tabel Penumpang
                'penumpang' => $tiket->penumpang ? [
                    'id' => $tiket->penumpang->id,
                    'nama' => $tiket->penumpang->nama,
                    'nomorTelepon' => $tiket->penumpang->nomor_telepon,
                ] : null,

                // Data Dari Tabel Jadwal
                'jadwal' => $tiket->jadwal ? [
                    'id' => $tiket->jadwal->id,
                    'namaJadwal' => $tiket->jadwal->nama_jadwal,
                    'waktuBerangkat' => $tiket->jadwal->waktu_berangkat,
                    'waktuTiba' => $tiket->jadwal->waktu_tiba,
                    'lokasiBerangkat' => $tiket->jadwal->lokasi_berangkat,
                    'lokasiTiba' => $tiket->jadwal->lokasi_tiba,
                    'biayaPerjalanan' => $tiket->jadwal->biaya_perjalanan,
                    'biayaPenumpang' => $tiket->jadwal->biaya_penumpang,
                    'biayaMotor' => $tiket->jadwal->biaya_motor,
                    'biayaMobil' => $tiket->jadwal->biaya_mobil,
                    'pajak' => $tiket->jadwal->pajak,
                    'diskon' => $tiket->jadwal->diskon,
                    'kapasitas' => $tiket->jadwal->kapasitas,
                    'namaKapal' => $tiket->jadwal->nama_kapal,
                ] : null,
            ];

            // Berikan response JSON
            return response()->json([
                'data' => $data,
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
                'request' => $id
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateJadwalRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $findTiket = Tiket::findOrFail($id);

            $findTiket->update([
                'penumpang_id' => $data['penumpangId'],
                'jadwal_id' => $data['jadwalId'],
                'penumpang_list' => json_encode($data['penumpangList']),
                'nomor_kendaraan' => $data['nomorKendaraan'],
                'jenis_kendaraan' => $data['jenisKendaraan'],
                'biaya_tiket' => $data['biayaTiket'],
                'status' => $data['status'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Jadwal berhasil diupdate',
                'data' => $findTiket,
                'request' => $data
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => [
                    'id' => $id,
                    'request' => $request->all(),
                ]
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $findTiket = Tiket::with(['penumpang', 'jadwal'])->findOrFail($id);

            $data = [
                'id' => $findTiket->id,
                'penumpangId' => $findTiket->penumpang_id,
                'jadwalId' => $findTiket->jadwal_id,
                'status' => $findTiket->status,
                'penumpangList' => json_decode($findTiket->penumpang_list),
                'nomorKendaraan' => $findTiket->nomor_kendaraan,
                'jenisKendaraan' => $findTiket->jenis_kendaraan,
                'kodeUnik' => $findTiket->kode_unik,
                'biayaTiket' => $findTiket->biaya_tiket,

                // Data Dari Tabel Penumpang
                'penumpang' => $findTiket->penumpang ? [
                    'id' => $findTiket->penumpang->id,
                    'nama' => $findTiket->penumpang->nama,
                    'nomorTelepon' => $findTiket->penumpang->nomor_telepon,
                ] : null,

                // Data Dari Tabel Jadwal
                'jadwal' => $findTiket->jadwal ? [
                    'id' => $findTiket->jadwal->id,
                    'namaJadwal' => $findTiket->jadwal->nama_jadwal,
                    'waktuBerangkat' => $findTiket->jadwal->waktu_berangkat,
                    'waktuTiba' => $findTiket->jadwal->waktu_tiba,
                    'lokasiBerangkat' => $findTiket->jadwal->lokasi_berangkat,
                    'lokasiTiba' => $findTiket->jadwal->lokasi_tiba,
                    'biayaPerjalanan' => $findTiket->jadwal->biaya_perjalanan,
                    'biayaPenumpang' => $findTiket->jadwal->biaya_penumpang,
                    'biayaMotor' => $findTiket->jadwal->biaya_motor,
                    'biayaMobil' => $findTiket->jadwal->biaya_mobil,
                    'pajak' => $findTiket->jadwal->pajak,
                    'diskon' => $findTiket->jadwal->diskon,
                    'kapasitas' => $findTiket->jadwal->kapasitas,
                    'namaKapal' => $findTiket->jadwal->nama_kapal,
                ] : null,
            ];

            $findTiket->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Tiket ' . $id . ' berhasil dihapus',
                'data' => $data,
                'request' => $id
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan atau telah dihapus'
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'request' => $id
            ]);
        }
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(string $id)
    {
        try {
            $findTiket = Tiket::onlyTrashed()->with(['penumpang', 'jadwal'])->findOrFail($id);

            $findTiket->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Tiket ' . $id . ' berhasil dipulihkan',
                'request' => $id,
                'data' => [
                    'id' => $findTiket->id,
                    'penumpangId' => $findTiket->penumpang_id,
                    'jadwalId' => $findTiket->jadwal_id,
                    'status' => $findTiket->status,
                    'penumpangList' => json_decode($findTiket->penumpang_list),
                    'nomorKendaraan' => $findTiket->nomor_kendaraan,
                    'jenisKendaraan' => $findTiket->jenis_kendaraan,
                    'kodeUnik' => $findTiket->kode_unik,
                    'biayaTiket' => $findTiket->biaya_tiket,

                    // Data Dari Tabel Penumpang
                    'penumpang' => $findTiket->penumpang ? [
                        'id' => $findTiket->penumpang->id,
                        'nama' => $findTiket->penumpang->nama,
                        'nomorTelepon' => $findTiket->penumpang->nomor_telepon,
                    ] : null,

                    // Data Dari Tabel Jadwal
                    'jadwal' => $findTiket->jadwal ? [
                        'id' => $findTiket->jadwal->id,
                        'namaJadwal' => $findTiket->jadwal->nama_jadwal,
                        'waktuBerangkat' => $findTiket->jadwal->waktu_berangkat,
                        'waktuTiba' => $findTiket->jadwal->waktu_tiba,
                        'lokasiBerangkat' => $findTiket->jadwal->lokasi_berangkat,
                        'lokasiTiba' => $findTiket->jadwal->lokasi_tiba,
                        'biayaPerjalanan' => $findTiket->jadwal->biaya_perjalanan,
                        'biayaPenumpang' => $findTiket->jadwal->biaya_penumpang,
                        'biayaMotor' => $findTiket->jadwal->biaya_motor,
                        'biayaMobil' => $findTiket->jadwal->biaya_mobil,
                        'pajak' => $findTiket->jadwal->pajak,
                        'diskon' => $findTiket->jadwal->diskon,
                        'kapasitas' => $findTiket->jadwal->kapasitas,
                        'namaKapal' => $findTiket->jadwal->nama_kapal,
                    ] : null,
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tiket ' . $id . ' yang terhapus tidak dapat ditemukan, mungkin telah dipulihkan sebelumnya.',
                'request' => $id
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'request' => $id
            ]);
        }
    }

    /**
     * Validate Tiket From PASS SCAN
     * 
     * 1.) Validasi apakah tiket ada di database berdasarkan kode uniknya
     * 2.) Validasi apakah jadwal yang pada ditiket sudah memasuki masa Wait, Open Gate, Arrival Threshold
     *      Misal jadwal jam 14.00 WIB
     *      Wait : Masa sebelum 2 jam jadwal, (Masa lalu - 12.00 WIB)
     *      Open Gate : Masa 2 jam sebelum jadwal hingga 15 menit sebelum jadwal, (12.00 WIB - 13.45 WIB)
     *      Arrival Threshold : Masa sebelum 15 menit setelah jadwal hingga seterusnya, (13.45 WIB - Masa depan)
     * 
     *      2.1.) Jika berdasarkan waktu sekarang tiket masih berada di Wait, maka hasil valdiasi yaitu error, dengan pesan "Jadwal Keberangkatan belum membuka gerbang (open gate), Silahkan Menunggu diluar pelabuhan",
     *      2.2.) Jika berdasarkan waktu sekarang tiket masih berada di Open Gate, maka hasil valdiasi yaitu success, dengan pesan "Jadwal Keberangkatan sudah membuka gerbang (open gate), Silahkan Masuk",
     *      2.3.) Jika berdasarkan waktu sekarang tiket masih berada di Arrival Threshold, maka hasil valdiasi yaitu error, dengan pesan "Jadwal Keberangkatan sudah ditutup,, Silahkan Keluar dari pelabuhan",
     * 
     * 3.) Valdasi apakan kendaraan di pelabuhan saat ini sesuai yang didaftarkan
     *     Jika hasil nya tidak sesuai maka hasil validasi yaitu error, dengan pesan "Kendaraan tidak sesuai, Silahkan Keluar dari pelabuhan"
     *     Jika hasil nya sesuai maka hasil validasi yaitu success, dengan pesan "Kendaraan sesuai, Silahkan Masuk"
     */
    public function validate(ValidateTiketRequest $request)
    {
        try {
            $data = $request->validated();

            $findTiket = Tiket::with(['jadwal'])->where('kode_unik', $data['kodeUnik'])->first();

            $platKendaraan = $request->nomorKendaraan;

            $platKendaraanDB = $findTiket->nomor_kendaraan;

            $waktuDatang = Carbon::parse($request->waktuDatang);

            $waktuJadwal = Carbon::parse($findTiket->jadwal->waktu_tiba);

            // 1.) Validasi apakah tiket ada di database berdasarkan kode uniknya
            if (!$findTiket) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tiket tidak ditemukan',
                    'request' => $data
                ]);
            } else {
                $selisihWaktu = $waktuDatang->diffInHours($waktuJadwal, false);
                // dd($waktuDatang, $waktuJadwal, $selisihWaktu);

                if ($selisihWaktu > 2) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Jadwal Keberangkatan belum membuka gerbang (open gate), Silahkan Menunggu diluar pelabuhan',
                        'selisihWaktu' => $selisihWaktu,
                        'request' => $data
                    ]);
                } elseif ($selisihWaktu <= 2 and $selisihWaktu >= 0.25) {
                    if ($platKendaraan == $platKendaraanDB) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Jadwal Keberangkatan sudah membuka gerbang (open gate), Silahkan Masuk',
                            'selisihWaktu' => $selisihWaktu,
                            'request' => $data
                        ]);
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Kendaraan tidak sesuai, Silahkan Keluar dari pelabuhan',
                            'selisihWaktu' => $selisihWaktu,
                            'request' => $data,
                            'platKendaraanSebenarnya' => $findTiket->nomor_kendaraan
                        ]);
                    }
                } elseif ($selisihWaktu < 0.25) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Jadwal Keberangkatan sudah ditutup, Silahkan Keluar dari pelabuhan',
                        'selisihWaktu' => $selisihWaktu,
                        'request' => $data
                    ]);
                }
            }

            // 2.) Validasi apakah jadwal yang pada ditiket sudah memasuki masa Wait, Open Gate, Arrival Threshold

        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'request' => $request->all()
            ]);
        }
    }

    public function validateWithOutNomorKendaraan(ValidateTiketWithOutNomorKendaraanRequest $request)
    {
        try {
            $data = $request->validated();

            $findTiket = Tiket::with(['jadwal'])->where('kode_unik', $data['kodeUnik'])->first();

            $waktuDatang = Carbon::parse($request->waktuDatang);

            $waktuJadwal = Carbon::parse($findTiket->jadwal->waktu_tiba);

            // 1.) Validasi apakah tiket ada di database berdasarkan kode uniknya
            if (!$findTiket) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tiket tidak ditemukan',
                    'request' => $data
                ]);
            } else {
                $selisihWaktu = $waktuDatang->diffInHours($waktuJadwal, false);
                // dd($waktuDatang, $waktuJadwal, $selisihWaktu);

                if ($selisihWaktu > 2) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Jadwal Keberangkatan belum membuka gerbang (open gate), Silahkan Menunggu diluar pelabuhan',
                        'selisihWaktu' => $selisihWaktu,
                        'request' => $data
                    ]);
                } elseif ($selisihWaktu <= 2 and $selisihWaktu >= 0.25) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Jadwal Keberangkatan sudah membuka gerbang (open gate), Silahkan Masuk',
                        'selisihWaktu' => $selisihWaktu,
                        'request' => $data
                    ]);
                } elseif ($selisihWaktu < 0.25) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Jadwal Keberangkatan sudah ditutup, Silahkan Keluar dari pelabuhan',
                        'selisihWaktu' => $selisihWaktu,
                        'request' => $data
                    ]);
                }
            }

            // 2.) Validasi apakah jadwal yang pada ditiket sudah memasuki masa Wait, Open Gate, Arrival Threshold

        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'request' => $request->all()
            ]);
        }
    }
}
