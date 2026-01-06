<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Api\V1\Tiket\StoreTiketRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Jadwal\UpdateJadwalRequest;
use App\Http\Requests\Api\V1\Tiket\UpdateTiketRequest;
use App\Http\Requests\Api\V1\Tiket\ValidateTiketRequest;
use App\Http\Requests\Api\V1\Tiket\ValidateTiketWithOutNomorKendaraanRequest;
use App\Models\Hardware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Jadwal;
use App\Models\Tiket;
use Throwable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class TiketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // =========================
            // 1) META GLOBAL (TIDAK TERPENGARUH QUERY)
            // =========================
            $terlambat = now(); // atau now()->subMinutes(15)

            $metaGlobal = [
                'total' => Tiket::count(),
                'menungguVerifikasi' => Tiket::where('status', 'menunggu_verifikasi')->count(),
                'terverifikasi' => Tiket::where('status', 'terverifikasi')->count(),
                'dibatalkan' => Tiket::where('status', 'dibatalkan')->count(),
                'kadaluarsa' => Tiket::whereHas('jadwal', fn ($q) => $q->where('waktu_berangkat', '<=', $terlambat))->count(),
            ];
            $metaGlobal['dibatalkanKadaluarsa'] = (int) ($metaGlobal['dibatalkan'] + $metaGlobal['kadaluarsa']);

            // =========================
            // 2) QUERY DATA (DENGAN FILTER DARI FRONTEND)
            // =========================
            $filteredQuery = Tiket::query()->with(['penumpang', 'jadwal']);

            // no = id tiket
            $filteredQuery->when($request->filled('no'), fn ($q) =>
                $q->where('id', (int) $request->query('no'))
            );

            $filteredQuery->when($request->filled('status'), function ($q) use ($request) {
                $normalized = $this->normalizeStatus($request->query('status'));

                if ($normalized !== null) {
                    $q->where('status', $normalized);
                }
            });            

            // jenisKendaraan (asumsi kolom: tikets.jenis_kendaraan)
            $filteredQuery->when($request->filled('jenisKendaraan'), fn ($q) =>
                $q->where('jenis_kendaraan', $request->query('jenisKendaraan'))
            );

            // namaPengguna = penumpang.name
            $filteredQuery->when($request->filled('namaPengguna'), function ($q) use ($request) {
                $keyword = $request->query('namaPengguna');
                $q->whereHas('penumpang', fn ($p) => $p->where('name', 'like', "%{$keyword}%"));
            });

            // idPenumpang = penumpang_id tiket
            $filteredQuery->when($request->filled('idPenumpang'), fn ($q) =>
                $q->where('penumpang_id', (int) $request->query('idPenumpang'))
            );            

            // rutePerjalanan = jadwal.nama_jadwal / lokasi_berangkat / lokasi_tiba
            $filteredQuery->when($request->filled('rutePerjalanan'), function ($q) use ($request) {
                $keyword = $request->query('rutePerjalanan');
                $q->whereHas('jadwal', function ($j) use ($keyword) {
                    $j->where(function ($w) use ($keyword) {
                        $w->where('nama_jadwal', 'like', "%{$keyword}%")
                        ->orWhere('lokasi_berangkat', 'like', "%{$keyword}%")
                        ->orWhere('lokasi_tiba', 'like', "%{$keyword}%");
                    });
                });
            });

            $filteredQuery->when($request->filled('waktu'), function ($q) use ($request) {
                $this->applyWaktuFilter($q, (string) $request->query('waktu'));
            });            

            // =========================
            // 3) totalKueri (sebelum vs sesudah filter)
            // =========================
            $totalFiltered = (clone $filteredQuery)->count(); // clone untuk pakai query yang sama [web:127][web:131]

            // =========================
            // 4) AMBIL DATA + MAPPING
            // =========================
            $tikets = $filteredQuery->orderByDesc('id')->get();

            $data = $tikets->map(function ($tiket) {
                return [
                    'id' => $tiket->id,
                    'penumpangId' => $tiket->penumpang_id,
                    'jadwalId' => $tiket->jadwal_id,
                    'status' => $tiket->status,
                    'penumpangList' => json_decode($tiket->penumpang_list, true),
                    'nomorKendaraan' => $tiket->nomor_kendaraan,
                    'jenisKendaraan' => $tiket->jenis_kendaraan,
                    'kodeUnik' => $tiket->kode_unik,
                    'biayaTiket' => $tiket->biaya_tiket,

                    'penumpang' => $tiket->penumpang ? [
                        'id' => $tiket->penumpang->id,
                        'nama' => $tiket->penumpang->name,
                        'nomorTelepon' => $tiket->penumpang->nomor_telepon,
                    ] : null,

                    'jadwal' => $tiket->jadwal ? [
                        'id' => $tiket->jadwal->id,
                        'namaJadwal' => $tiket->jadwal->nama_jadwal,
                        'waktuBerangkat' => $tiket->jadwal->waktu_berangkat,
                        'waktuTiba' => $tiket->jadwal->waktu_tiba,
                        'lokasiBerangkat' => $tiket->jadwal->lokasi_berangkat,
                        'lokasiTiba' => $tiket->jadwal->lokasi_tiba,
                        'namaKapal' => $tiket->jadwal->nama_kapal,
                    ] : null,
                ];
            });

            return response()->json([
                'data' => $data,
                'meta' => $metaGlobal + [
                    'totalKueri' => $totalFiltered,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
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
                    'nama' => $tiket->penumpang->name,
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
                    'nama' => $tiket->penumpang->name,
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
    public function update(UpdateTiketRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $findTiket = Tiket::findOrFail($id);

            $findTiket->update([
                'penumpang_id' => $data['penumpangId'] ?? $findTiket['penumpang_id'],
                'jadwal_id' => $data['jadwalId'] ?? $findTiket['jadwal_id'],
                'penumpang_list' => array_key_exists('penumpangList', $data) ? json_encode($data['penumpangList']) : $findTiket->penumpang_list,
                'nomor_kendaraan' => $data['nomorKendaraan'] ?? $findTiket['nomor_kendaraan'],
                'jenis_kendaraan' => $data['jenisKendaraan'] ?? $findTiket['jenis_kendaraan'],
                'biaya_tiket' => $data['biayaTiket'] ?? $findTiket['biaya_tiket'],
                'status' => $data['status'] ?? $findTiket['status'],
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
                    'nama' => $findTiket->penumpang->name,
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
                        'nama' => $findTiket->penumpang->name,
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
                    $log = Hardware::create([
                        'code' => Str::uuid(),
                        'title' => "Validasi Tiket #" . $findTiket['id'],
                        'desc' => "Validasi Tiket #" . $findTiket['id'] . "Pada waktu " . $waktuDatang,
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Jadwal Keberangkatan sudah membuka gerbang (open gate), Silahkan Masuk',
                        'selisihWaktu' => $selisihWaktu,
                        'request' => $data,
                        'data' => [
                            'idTiket' => $findTiket['id'],
                            'checkInCode' => $log['code'],
                        ]
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

    private function applyWaktuFilter(Builder $filteredQuery, string $input): void
    {
        $s = trim(mb_strtolower($input));
        $s = str_replace(['wib', ',', "\t"], [' ', ' ', ' '], $s);
        $s = preg_replace('/\s+/', ' ', $s);

        // 1) Deteksi hari (Indonesia) -> MySQL DAYOFWEEK: 1=Sunday ... 7=Saturday
        // Minggu=1, Senin=2, Selasa=3, Rabu=4, Kamis=5, Jumat=6, Sabtu=7 [web:277]
        $dowMap = [
            'minggu' => 1,
            'ahad'   => 1,
            'senin'  => 2,
            'selasa' => 3,
            'rabu'   => 4,
            'kamis'  => 5,
            'jumat'  => 6,
            'sabtu'  => 7,
        ];

        $dow = null;
        foreach ($dowMap as $name => $num) {
            if (preg_match('/\b'.preg_quote($name, '/').'\b/u', $s)) {
                $dow = $num;
                $s = preg_replace('/\b'.preg_quote($name, '/').'\b/u', ' ', $s);
                $s = preg_replace('/\s+/', ' ', trim($s));
                break;
            }
        }

        // 2) Deteksi jam: "10:10", "10.10", "10:10 WIB", "10.10 WIB"
        $time = null;
        if (preg_match('/\b([01]?\d|2[0-3])[:.]([0-5]\d)\b/u', $s, $m)) {
            $time = sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
            $s = str_replace($m[0], ' ', $s);
            $s = preg_replace('/\s+/', ' ', trim($s));
        }

        // 3) Deteksi tanggal numerik: 10-11-2025, 10/11/2025, 10,11,2025, 10.11.2025
        $date = null;
        if (preg_match('/\b(\d{1,2})[\/\-,.](\d{1,2})[\/\-,.](\d{4})\b/u', $s, $m)) {
            $candidate = sprintf('%02d-%02d-%04d', (int) $m[1], (int) $m[2], (int) $m[3]);

            // createFromFormat agar "d-m-Y" pasti dianggap day-month-year [web:268]
            try {
                $date = Carbon::createFromFormat('d-m-Y', $candidate)->toDateString();
            } catch (\Throwable $e) {
                $date = null;
            }

            $s = str_replace($m[0], ' ', $s);
            $s = preg_replace('/\s+/', ' ', trim($s));
        }

        // 4) Deteksi bulan (Indonesia) + tahun (opsional)
        $monthMap = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        ];

        $month = null;
        foreach ($monthMap as $name => $num) {
            if (preg_match('/\b'.preg_quote($name, '/').'\b/u', $s)) {
                $month = $num;
                $s = preg_replace('/\b'.preg_quote($name, '/').'\b/u', ' ', $s);
                $s = preg_replace('/\s+/', ' ', trim($s));
                break;
            }
        }

        $year = null;
        if (preg_match('/\b(19|20)\d{2}\b/', $s, $m)) {
            $year = (int) $m[0];
        }

        // 5) Terapkan filter ke relasi jadwal.waktu_berangkat
        $filteredQuery->whereHas('jadwal', function ($j) use ($dow, $date, $month, $year, $time) {
            // Hari (MySQL-specific): DAYOFWEEK() mengembalikan 1..7 (Sunday..Saturday) [web:277]
            if ($dow !== null) {
                $j->whereRaw('DAYOFWEEK(waktu_berangkat) = ?', [$dow]);
            }

            // Tanggal lengkap
            if ($date !== null) {
                $j->whereDate('waktu_berangkat', $date); // whereDate untuk bagian tanggal [web:266]
            }

            // Bulan / Tahun parsial
            if ($month !== null) {
                $j->whereMonth('waktu_berangkat', $month); // whereMonth [web:266]
            }
            if ($year !== null) {
                $j->whereYear('waktu_berangkat', $year); // whereYear [web:266]
            }

            // Jam saja / jam+tanggal
            if ($time !== null) {
                $j->whereTime('waktu_berangkat', $time);
            }
        });
    }

    private function normalizeStatus(?string $input): ?string
    {
        if ($input === null) return null;

        $s = trim(mb_strtolower($input));

        // samakan pemisah & buang karakter non huruf/underscore/spasi
        $s = str_replace(['-', '.', ','], ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        $s2 = str_replace(' ', '_', $s); // untuk kasus "menunggu verifikasi" -> "menunggu_verifikasi"

        // MENUNGGU_VERIFIKASI
        if (
            str_contains($s2, 'menunggu_verifikasi') ||
            str_contains($s, 'menunggu') ||
            str_contains($s, 'tunggu')
        ) {
            return 'menunggu_verifikasi';
        }

        // TERVERIFIKASI (tangkap typo umum: terverikasi)
        if (
            str_contains($s, 'terverifikasi') ||
            str_contains($s, 'terverikasi') ||
            str_contains($s, 'diverifikasi') ||
            str_contains($s, 'verifikasi') ||
            str_contains($s, 'verif')
        ) {
            return 'terverifikasi';
        }

        // DIBATALKAN
        if (
            str_contains($s, 'dibatalkan') ||
            str_contains($s, 'terbatalkan') ||
            str_contains($s, 'batal')
        ) {
            return 'dibatalkan';
        }

        // kalau tidak cocok, biarkan null (anggap invalid / tidak difilter)
        return null;
    }
}
