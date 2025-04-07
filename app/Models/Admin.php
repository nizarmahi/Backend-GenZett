<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $primaryKey = 'adminId';

    protected $fillable = [
        'userId', 'locationId'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'userId');
    }

    public function location() {
        return $this->belongsTo(Location::class, 'locationId');
    }
}
