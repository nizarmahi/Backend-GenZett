<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $primaryKey = 'memberId';

    protected $fillable = [
        'userId', 'valid_until'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'userId');
    }
}
