<?php

namespace App\Models;

use App\Enum\GameStatus;
use App\Enum\GameType;
use App\Enum\SendStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Result extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'game',
        'number',
        'table',
        'send_status',
    ];

    public static function setResults(GameType $gameType, int $number, array $table): void
    {
        $model = new Result();

        $model->game = $gameType;
        $model->number = $number;
        $model->send_status = SendStatus::IN_QUEUE;
        $model->results = json_encode($table, JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_SLASHES);

        $model->save();
    }

    public static function getInQueue(): Collection
    {
        return Result::where('send_status', SendStatus::IN_QUEUE)->get();
    }

    public static function setSend(Result $result): void
    {
        $result->send_status = SendStatus::SEND;
        $result->save();
    }
}
