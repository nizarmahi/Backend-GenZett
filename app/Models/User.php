<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'userId';

    protected $fillable = [
        'username', 'name', 'email', 'phone', 'password', 'level'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'userId');
    }

    public function member()
    {
        return $this->hasOne(Member::class, 'userId');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'userId');
    }
}
