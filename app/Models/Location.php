<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $primaryKey = 'locationId';

    protected $fillable = [
        'locationName', 'description', 'locationPath'
    ];

    public function fields() {
        return $this->hasMany(Field::class, 'locationId');
    }

    public function admins() {
        return $this->hasMany(Admin::class, 'locationId');
    }
}
