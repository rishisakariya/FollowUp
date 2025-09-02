<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    // Since the table name doesn't follow Laravel's plural convention, explicitly define it
    protected $table = 'password_resets';

    // Primary key is 'id', so no need to specify unless different

    // Disable timestamps (no updated_at column)
    public $timestamps = false;

    // Allow mass assignment on these fields
    protected $fillable = [
        'email',
        'otp',
        'verified_at',
        'created_at',
        'expires_at',
    ];

    // Cast timestamps to Carbon instances automatically
    protected $dates = [
        'verified_at',
        'created_at',
        'expires_at',
    ];

    // Relationship: PasswordReset belongs to a User (via email)
    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
