<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $primaryKey = 'reservationId';

    protected $fillable = [
        'userId', 'status', 'name', 'paymentStatus', 'total', 'remaining'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'userId');
    }

    public function details() {
        return $this->hasMany(ReservationDetail::class, 'reservationId');
    }

    public function payment() {
        return $this->hasOne(Payment::class, 'reservationId');
    }
}
