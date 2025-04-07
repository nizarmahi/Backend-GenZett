<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;

    protected $primaryKey = 'memberId';

    protected $fillable = ['userId', 'valid_until'];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
