<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'paymentId';

    protected $fillable = [
        'reservationId', 'invoiceDate', 'totalPaid'
    ];

    public function reservation() {
        return $this->belongsTo(Reservation::class, 'reservationId');
    }
}
