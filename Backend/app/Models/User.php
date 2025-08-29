<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
// class User extends Model
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    // public $timestamps = false;

    // ✅ Enable created_at and updated_at timestamps
    public $timestamps = true;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token', // ✅ Hide token from API output
    ];

    // ✅ Cast email_verified_at to datetime
    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    // ];
}
