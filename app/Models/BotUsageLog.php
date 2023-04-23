<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotUsageLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bot',
        'username',
        'user_id',
        'command',
    ];
}
