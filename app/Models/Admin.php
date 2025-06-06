<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Model
{

    use HasFactory, Notifiable;

    protected $primaryKey = 'adminId';

    protected $fillable = [
        'userId',
        'locationId'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'locationId');
    }
}
