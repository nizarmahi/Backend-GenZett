<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'accountNumber',
        'paymentPlatform',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
