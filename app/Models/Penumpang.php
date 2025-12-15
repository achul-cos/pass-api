<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Ticket;

class Penumpang extends Model
{
    /** @use HasFactory<\Database\Factories\PenumpangFactory> */
    use HasFactory;

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
