<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Followup extends Model
{
    use HasFactory;
    protected $primaryKey = 'task_id';
    public $timestamps = false;
    protected $fillable = [
        'title',
        'creator_user_id',
        'creator_receiver_id',
        'description',
        'status',
        'date',
        'set_reminder',
        'time',
    ];
    //relation between receiver and followups table
    public function receiver()
    {
        return $this->belongsTo(Receiver::class, 'creator_receiver_id', 'receiver_id');
    }
    public function creatorUser()
    {
        return $this->belongsTo(User::class, 'creator_user_id', 'user_id');
    }
}
