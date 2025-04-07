<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;

    protected $primaryKey = 'locationId';

    protected $fillable = ['locationName', 'description', 'locationPath'];

    public function fields()
    {
        return $this->hasMany(Field::class, 'locationId');
    }

    public function admins()
    {
        return $this->hasMany(Admin::class, 'locationId');
    }
}
