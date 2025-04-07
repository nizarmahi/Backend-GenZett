<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    protected $primaryKey = 'sportId';

    protected $fillable = [
        'sportName', 'description'
    ];

    public function fields() {
        return $this->hasMany(Field::class, 'sportId');
    }
}
