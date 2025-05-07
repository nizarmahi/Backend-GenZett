<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sport extends Model
{
    use HasFactory;

    protected $primaryKey = 'sportId';

    protected $fillable = ['sportName', 'description'];

    public function fields()
    {
        return $this->hasMany(Field::class, 'sportId');
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class, 'sportId');
    }
}
