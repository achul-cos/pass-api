<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tiket extends Model
{
    /** @use HasFactory<\Database\Factories\TiketFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'penumpang_id',
        'jadwal_id',
        'status',
        'penumpang_list',
        'nomor_kendaraan',
        'jenis_kendaraan',
        'kode_unik',
        'biaya_tiket',
    ];

    public function penumpang()
    {
        return $this->belongsTo(Penumpang::class);
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class);
    }
}
