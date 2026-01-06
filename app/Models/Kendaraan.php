<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kendaraan extends Model
{
    /** @use HasFactory<\Database\Factories\KendaraanFactory> */
    use HasFactory;

    protected $fillable = [
        'tiket_id',
        'penumpang_id',
        'jadwal_id',
        'parkir1_id',
        'parkir2_id',
        'waktu_check_in',
    ];

    public function tiket()
    {
        return $this->belongsTo(Tiket::class);
    }

    public function penumpang()
    {
        return $this->belongsTo(Penumpang::class);
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function parkir()
    {
        return $this->belongsTo(Parkir::class, 'parkir');
    }

    public function parkir1()
    {
        return $this->belongsTo(Parkir::class, 'parkir1_id');
    }

    public function parkir2()
    {
        return $this->belongsTo(Parkir::class, 'parkir2_id');
    }    
}
