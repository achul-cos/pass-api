<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Kendaraan\StoreKendaraanRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kendaraan;
use App\Models\Parkir;
use App\Models\Tiket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

    public function store(StoreKendaraanRequest $request)
    {
        // 1. Ambil Data Tervalidasi
        $validated = $request->validated();
        
        // Mapping input 'idTiket' ke variabel yang kita pakai
        $tiketId = $validated['idTiket'];
        
        DB::beginTransaction();
        try {
            // 2. Cek Tiket Bekas (Double Check-in)
            // Jika sudah ada kendaraan dengan tiket_id ini, return detail kendaraan tsb.
            $existingKendaraan = Kendaraan::with(['parkir1', 'parkir2'])->where('tiket_id', $tiketId)->first();
            
            if ($existingKendaraan) {
                // Siapkan data response untuk tiket yang sudah dipakai
                $p1 = $existingKendaraan->parkir1;
                $p2 = $existingKendaraan->parkir2;
                
                $kodeDisplay = 'OVERLOAD';
                $barisDisplay = '-';
                
                if ($p1) {
                    $kodeDisplay = $p2 ? $p1->kode_parkir . '-' . $p2->kode_parkir : $p1->kode_parkir;
                    $barisDisplay = $p2 ? $p1->baris . '&' . $p2->baris : $p1->baris;
                }

                // Return response khusus sesuai request user
                return response()->json([
                    'status' => 'success', // atau 'error' tergantung preferensi, tapi biasanya success dengan data
                    'message' => 'Tiket ini sudah digunakan untuk Check-In.',
                    'data' => [
                        'kendaraan_id' => $existingKendaraan->id,
                        'is_overload' => is_null($p1),
                        'kode_parkir' => $kodeDisplay,
                        'baris' => $barisDisplay,
                    ]
                ], 200); // 200 OK karena data ditemukan
            }

            // 3. Setup Data Baru
            $tiket = Tiket::with('jadwal')->findOrFail($tiketId); 
            $jadwalBaruId = $tiket->jadwal_id;
            $jenisKendaraan = $tiket->jenis_kendaraan;

            // 4. Refresh Status Slot (Hapus yang sudah expired / Ambil yang aktif)
            $now = Carbon::now();
            
            // Ambil slot yang sedang terisi oleh kendaraan yang BELUM berangkat
            $kendaraanAktif = Kendaraan::with('jadwal')
                ->whereHas('jadwal', fn($q) => $q->where('waktu_berangkat', '>', $now))
                ->whereNotNull('parkir1_id') // Hanya ambil yang punya slot
                ->get();

            $occupiedSlots = [];
            foreach ($kendaraanAktif as $k) {
                if ($k->parkir1_id) $occupiedSlots[$k->parkir1_id] = $k->jadwal_id;
                if ($k->parkir2_id) $occupiedSlots[$k->parkir2_id] = $k->jadwal_id;
            }

            // Setup Grid Parkir
            $allParkir = Parkir::all(); 
            $grid = [];
            foreach ($allParkir as $p) {
                $grid[$p->kolom][$p->baris] = $p;
            }

            // 5. Jalankan Algoritma (Sekarang Prioritas Kolom)
            $allocation = $this->findBestSlot(
                $jenisKendaraan, 
                $jadwalBaruId, 
                $grid, 
                $occupiedSlots
            );

            // 6. Tentukan ID Parkir (Isi ID jika dapat, Null jika Overload)
            $parkir1Id = null;
            $parkir2Id = null;
            $statusPesan = 'OVERLOAD';
            $kodeDisplay = 'OVERLOAD';
            $barisDisplay = '-';

            if ($allocation) {
                // Jika dapat slot
                $parkir1Id = $allocation['slot1']->id;
                $parkir2Id = $allocation['slot2'] ? $allocation['slot2']->id : null;
                
                $statusPesan = 'Check-in Berhasil. Slot Ditemukan.';
                $kodeDisplay = $allocation['kode_display'];
                $barisDisplay = $allocation['baris_display'];
            } else {
                // Jika Overload
                $statusPesan = 'Check-in Berhasil (OVERLOAD). Tidak ada slot parkir tersedia.';
            }

            // 7. Simpan Data Kendaraan
            $kendaraan = Kendaraan::create([
                'tiket_id' => $tiket->id,
                'penumpang_id' => $tiket->penumpang_id,
                'jadwal_id' => $tiket->jadwal_id,
                'parkir1_id' => $parkir1Id, // Bisa null
                'parkir2_id' => $parkir2Id, // Bisa null
                'waktu_check_in' => $now,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $statusPesan,
                'data' => [
                    'kendaraan_id' => $kendaraan->id,
                    'is_overload' => is_null($parkir1Id),
                    'kode_parkir' => $kodeDisplay,
                    'baris' => $barisDisplay,
                ]
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
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

    /**
     * Algoritma Inti: Mencari Slot dengan Skor Tertinggi
     * Prioritas: Kolom yang sama dengan jadwal yang sama
     */
    private function findBestSlot($jenis, $targetJadwalId, $grid, $occupiedSlots)
    {
        $candidates = [];
        $maxRow = 6; // A-R (18 slot) -> 3 col x 6 row
        $maxCol = 3;

        // Loop semua kemungkinan posisi
        for ($c = 1; $c <= $maxCol; $c++) {
            
            // Analisa Status Kolom Dulu untuk Scoring
            $colStatus = $this->analyzeColumn($c, $grid, $occupiedSlots, $maxRow);
            
            // Hitung Base Score untuk Kolom ini
            $colScore = 0;
            if ($colStatus['status'] === 'same_schedule' && $colStatus['jadwal_id'] == $targetJadwalId) {
                $colScore = 1000; // Prioritas Super Utama: Kolom Teman Sejadwal
            } elseif ($colStatus['status'] === 'empty') {
                $colScore = 500;  // Prioritas Kedua: Kolom Kosong Melompong
            } else {
                $colScore = 100;  // Prioritas Terakhir: Kolom Campur/Jadwal Lain
            }

            for ($r = 1; $r <= $maxRow; $r++) {
                
                // Cek ketersediaan fisik slot dasar
                $p1 = $grid[$c][$r] ?? null;
                if (!$p1) continue; 
                
                // Jika slot 1 sudah terisi, skip
                if (isset($occupiedSlots[$p1->id])) continue;

                $slot1 = $p1;
                $slot2 = null;
                
                // Logika MOBIL (Butuh 2 Slot Vertikal: Baris r dan r+1)
                if ($jenis === 'mobil') {
                    // Cek apakah ada baris bawahnya
                    if ($r + 1 > $maxRow) continue; // Tidak cukup ruang ke belakang

                    $p2 = $grid[$c][$r + 1] ?? null;
                    if (!$p2) continue;
                    
                    // Cek slot 2 terisi atau tidak
                    if (isset($occupiedSlots[$p2->id])) continue;

                    $slot2 = $p2;
                }

                // --- FINAL SCORING ---
                // Score Akhir = Score Kolom - Penalty Baris (agar mengisi dari depan ke belakang)
                $finalScore = $colScore - $r; 

                $candidates[] = [
                    'slot1' => $slot1,
                    'slot2' => $slot2,
                    'score' => $finalScore,
                    'row' => $r,
                    'col' => $c,
                    'kode_display' => $slot2 ? $slot1->kode_parkir . '-' . $slot2->kode_parkir : $slot1->kode_parkir,
                    'baris_display' => $slot2 ? $r . '&' . ($r+1) : $r,
                ];
            }
        }

        // Jika tidak ada kandidat sama sekali
        if (empty($candidates)) return null;

        // Sort Kandidat: Score Tertinggi -> Kolom Terkecil -> Baris Terkecil
        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                // Jika score sama, prioritas kolom kiri dulu (opsional, bisa dibalik)
                if ($a['col'] === $b['col']) {
                    return $a['row'] <=> $b['row']; // Isi baris depan dulu
                }
                return $a['col'] <=> $b['col']; 
            }
            return $b['score'] <=> $a['score']; // Score besar di atas
        });

        // Ambil yang terbaik
        return $candidates[0];
    }

    /**
     * Helper: Menganalisa status suatu KOLOM
     * Apakah kolom ini: Kosong? Berisi jadwal X? Atau Campur?
     */
    private function analyzeColumn($col, $grid, $occupiedSlots, $maxRow)
    {
        $schedulesFound = [];
        // $isFull = true; // Tidak perlu, karena kita hanya butuh info jadwal

        for ($r = 1; $r <= $maxRow; $r++) {
            $p = $grid[$col][$r] ?? null;
            if ($p) {
                if (isset($occupiedSlots[$p->id])) {
                    $schedId = $occupiedSlots[$p->id];
                    if (!in_array($schedId, $schedulesFound)) {
                        $schedulesFound[] = $schedId;
                    }
                } 
            }
        }

        if (empty($schedulesFound)) {
            return ['status' => 'empty', 'jadwal_id' => null];
        }

        // Cek apakah kolom ini EKSKLUSIF punya satu jadwal tertentu?
        // (Atau dominan, tapi di sini kita pakai logika eksklusif dulu biar strict)
        if (count($schedulesFound) === 1) {
            return ['status' => 'same_schedule', 'jadwal_id' => $schedulesFound[0]];
        }

        return ['status' => 'mixed', 'jadwal_id' => null];
    }    
}
