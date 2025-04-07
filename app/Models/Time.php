<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Time extends Model
{
    use HasFactory;

    protected $primaryKey = 'timeId';

    protected $fillable = ['fieldId', 'time', 'status', 'price'];

    public function field()
    {
        return $this->belongsTo(Field::class, 'fieldId');
    }

    public function reservationDetails()
    {
        return $this->hasMany(ReservationDetail::class, 'timeId');
    }
}
