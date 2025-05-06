<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'userId';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'username',
        'name',
        'email',
        'phone',
        'password',
        'role',
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
        return $this->hasOne(Membership::class, 'userId');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'userId');
    }
}
