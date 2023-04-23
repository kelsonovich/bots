<?php

namespace App\Models;

use App\Enum\SendStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'text',
        'send_status',
    ];

    public static function getInQueue(): Collection
    {
        return Notification::where('send_status', SendStatus::IN_QUEUE)->limit(20)->get();
    }

    public static function setSend(Notification $notification): void
    {
        $notification->send_status = SendStatus::SEND;
        $notification->save();
    }
}
