<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    use HasFactory;

    protected $primaryKey = 'reservationId';

    protected $fillable = [
        'userId', 'name', 'paymentStatus', 'total', 'remaining'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function details()
    {
        return $this->hasMany(ReservationDetail::class, 'reservationId');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'reservationId');
    }
}
