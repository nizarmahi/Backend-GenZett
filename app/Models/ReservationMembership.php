<?php

Namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Reservation;
use App\Models\Membership;

class ReservationMembership extends Model
{
    use HasFactory;

    protected $primaryKey = 'reservationMemberId';

    protected $fillable = ['reservationId', 'membershipId'];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservationId');
    }

    public function membership()
    {
        return $this->belongsTo(Membership::class, 'membershipId');
    }
}
