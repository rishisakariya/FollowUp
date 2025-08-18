<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receiver extends Model
{
    use HasFactory;
    protected $table = 'receiver';
    protected $primaryKey = 'receiver_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'creator',
        'color',
    ];
}
