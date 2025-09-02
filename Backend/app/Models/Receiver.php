<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receiver extends Model
{
    use HasFactory;
    protected $table = 'receiver';
    protected $primaryKey = 'receiver_id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        // 'creator',
        'color',
        'created_by',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function followups()
    {
        return $this->hasMany(Followup::class, 'creator_receiver_id', 'receiver_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}
