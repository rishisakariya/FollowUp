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
        //user_id
        'name',
        'email',
        'password',
        'updated_by'
    ];

    protected $hidden = [
        'password',
        'remember_token', // ✅ Hide token from API output
    ];

    // ✅ Cast email_verified_at to datetime
    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    // ];

    // One-to-Many: A user can create many receivers
    public function receivers()
    {
        return $this->hasMany(Receiver::class, 'created_by'); // FK in receivers table
    }

    // One-to-Many: A user can create many follow-ups
    public function followups()
    {
        return $this->hasMany(FollowUp::class, 'created_by'); // FK in follow_ups table
    }
}
