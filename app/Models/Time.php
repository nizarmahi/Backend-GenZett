<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
    protected $primaryKey = 'timeId';

    protected $fillable = [
        'fieldId', 'time', 'status', 'price'
    ];

    public function field() {
        return $this->belongsTo(Field::class, 'fieldId');
    }

    public function reservationDetails() {
        return $this->hasMany(ReservationDetail::class, 'timeId');
    }
}
