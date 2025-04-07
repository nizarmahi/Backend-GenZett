<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservationDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'detailId';

    protected $fillable = ['reservationId', 'fieldId', 'timeId', 'date'];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservationId');
    }

    public function field()
    {
        return $this->belongsTo(Field::class, 'fieldId');
    }

    public function time()
    {
        return $this->belongsTo(Time::class, 'timeId');
    }
}
