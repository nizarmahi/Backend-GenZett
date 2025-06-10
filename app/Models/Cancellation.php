<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cancellation extends Model
{
    use HasFactory;

    protected $primaryKey = 'cancellationId';
    
    protected $fillable = [
        'reservationId',
        'accountName',
        'accountNumber',
        'paymentPlatform',
        'reason'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservationId', 'reservationId');
    }
}
