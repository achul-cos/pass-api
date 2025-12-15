<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jadwal extends Model
{
    /** @use HasFactory<\Database\Factories\JadwalFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nama_jadwal',
        'waktu_berangkat',
        'waktu_tiba',
        'lokasi_berangkat',
        'lokasi_tiba',
        'biaya_perjalanan',
        'biaya_penumpang',
        'biaya_motor',
        'biaya_mobil',
        'diskon',
        'kapasitas',
        'nama_kapal'
    ];

    public function tikets()
    {
        return $this->hasMany(Tiket::class);
    }
}
