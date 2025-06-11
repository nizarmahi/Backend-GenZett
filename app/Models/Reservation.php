<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    use HasFactory;

    protected $primaryKey = 'reservationId';

    protected $fillable = [
        'userId',
        'name',
        'paymentStatus',
        'total',
        'paymentType'
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
    public function membership()
    {
        return $this->belongsToMany(Membership::class, 'reservation_members', 'reservationId', 'membershipId')->withTimestamps();
    }
    public function cancellation()
    {
        return $this->hasOne(Cancellation::class, 'reservationId', 'reservationId');
    }
}
