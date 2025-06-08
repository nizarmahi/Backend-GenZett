<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryReservationUser extends Model
{
    use HasFactory;

    protected $table = 'history_reservation_user';
    protected $primaryKey = 'historyId';

    protected $fillable = [
        'reservationId',
        'userId',
        'bookingName',
        'cabang',
        'lapangan',
        'paymentStatus',
        'paymentType',
        'reservationStatus',
        'totalAmount',
        'totalPaid',
        'remainingAmount',
        'reservationDate',
        'details',
        'bankName',
        'accountName',
        'accountNumber',
        'cancelReason'
    ];

    protected $casts = [
        'details' => 'array',
        'totalAmount' => 'decimal:2',
        'totalPaid' => 'decimal:2',
        'remainingAmount' => 'decimal:2',
        'reservationDate' => 'date'
    ];

    // Relationships
    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservationId', 'reservationId');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'userId');
    }
}