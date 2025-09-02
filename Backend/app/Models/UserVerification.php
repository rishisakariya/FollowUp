<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'verify_user_id',
        'otp',
        'expires_at',
    ];

    protected $dates = [
        'expires_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'verify_user_id');
    }
}
