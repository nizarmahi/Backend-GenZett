<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Field extends Model
{
    use HasFactory;

    protected $primaryKey = 'fieldId';

    protected $fillable = ['locationId', 'sportId', 'name', 'description'];

    public function location()
    {
        return $this->belongsTo(Location::class, 'locationId');
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class, 'sportId');
    }

    public function times()
    {
        return $this->hasMany(Time::class, 'fieldId');
    }

    public function reservationDetails()
    {
        return $this->hasMany(ReservationDetail::class, 'fieldId');
    }
    public function scopeHasSport($query, $sports)
    {
        return $query->whereIn('sportId', $sports);
    }

    public function scopeHasLocation($query, $locations)
    {
        return $query->whereIn('locationId', $locations);
    }
    public function scopeSearch($query, $keyword)
    {
        return $query->where('name', 'like', '%' . $keyword . '%');
    }


}
