<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $primaryKey = 'paymentId';

    protected $fillable = [
        'reservationId',
        'invoiceDate',
        'totalPaid',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'xendit_status',
        'expiry_date',
    ];

    protected $casts = [
        'invoiceDate' => 'datetime',
        'expiry_date' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservationId');
    }
}
        