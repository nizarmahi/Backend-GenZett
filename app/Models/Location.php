<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;

    protected $primaryKey = 'locationId';

    protected $fillable = ['locationName', 'description', 'locationPath', 'address'];

    public function fields()
    {
        return $this->hasMany(Field::class, 'locationId');
    }
    public function memberships()
    {
        return $this->hasMany(Membership::class, 'locationId');
    }
    public function admins()
    {
        return $this->hasMany(Admin::class, 'locationId');
    }
    public function scopeHasSport($query, array $sports)
    {
        return $query->whereHas('fields.sport', function ($q) use ($sports) {
            $q->whereIn('sportName', $sports);
        });
    }
    public function scopeSearch($query, $term)
    {
        return $query->where('locationName', 'like', '%' . $term . '%')
                    ->orWhere('description', 'like', '%' . $term . '%');
    }
}
