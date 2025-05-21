<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Membership extends Model
{
    use HasFactory;

    protected $primaryKey = 'membershipId';

    protected $fillable = ['locationId','sportId', 'name', 'description', 'discount', 'weeks'];
    protected $casts = [
        'created_at' => 'datetime',
    ];
    protected $table = 'memberships';

    public function sports()
    {
        return $this->belongsTo(Sport::class, 'sportId', 'sportId');
    }
    public function locations()
    {
        return $this->belongsTo(Location::class, 'locationId', 'locationId');
    }
}
