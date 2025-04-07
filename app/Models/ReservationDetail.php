<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationDetail extends Model
{
    protected $primaryKey = 'detailId';

    protected $fillable = [
        'reservationId', 'fieldId', 'timeId', 'date'
    ];

    public function reservation() {
        return $this->belongsTo(Reservation::class, 'reservationId');
    }

    public function field() {
        return $this->belongsTo(Field::class, 'fieldId');
    }

    public function time() {
        return $this->belongsTo(Time::class, 'timeId');
    }
}
