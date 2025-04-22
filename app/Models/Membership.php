<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Membership extends Model
{
    use HasFactory;

    protected $primaryKey = 'membershipId';

    protected $fillable = ['name', 'description', 'price', 'weeks'];
    protected $casts = [
        'created_at' => 'datetime',
    ];
    protected $table = 'memberships';

    public function location()
    {
        return $this->belongsTo(Location::class, 'locationId');
    }
}
