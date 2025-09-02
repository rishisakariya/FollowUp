<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Followup extends Model
{
    use HasFactory;
    protected $primaryKey = 'task_id';
    public $timestamps = true;
    protected $fillable = [
        'title',
        // 'creator_user_id',
        'creator_receiver_id',
        'description',
        'status',
        'date',
        'set_reminder',
        'time',
        'created_by',
        'updated_by',
    ];
    // The user who created the followup
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // The user who last updated the followup
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }

    // The receiver related to this followup
    public function receiver()
    {
        return $this->belongsTo(Receiver::class, 'creator_receiver_id', 'receiver_id');
    }
}
