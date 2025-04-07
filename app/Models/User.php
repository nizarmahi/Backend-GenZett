<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'userId';

    protected $fillable = [
        'username', 'name', 'email', 'phone', 'password', 'level'
    ];

    public function reservations() {
        return $this->hasMany(Reservation::class, 'userId');
    }

    public function member() {
        return $this->hasOne(Member::class, 'userId');
    }

    public function admin() {
        return $this->hasOne(Admin::class, 'userId');
    }
}
