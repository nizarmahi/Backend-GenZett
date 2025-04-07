<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $primaryKey = 'fieldId';

    protected $fillable = [
        'locationId', 'sportId', 'name', 'description'
    ];

    public function location() {
        return $this->belongsTo(Location::class, 'locationId');
    }

    public function sport() {
        return $this->belongsTo(Sport::class, 'sportId');
    }

    public function times() {
        return $this->hasMany(Time::class, 'fieldId');
    }

    public function reservationDetails() {
        return $this->hasMany(ReservationDetail::class, 'fieldId');
    }
}
