<?php

namespace App\Models;

use App\Enum\GameStatus;
use App\Enum\GameType;
use App\Enum\SendStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'schedule';

    protected $fillable = [
        'game',
        'title',
        'number',
        'package',
        'full_title',
        'place',
        'price',
        'start',
        'status',
        'send_status',
    ];

    protected $casts = [
        'start'  => 'datetime:H:i:s d.m.Y',
    ];

    public static function getGameByTypeAndNumber(GameType $gameType, int $number): Schedule
    {
        $model = Schedule::where('number', $number)->where('game', $gameType)->first();

        if (! $model) {
            $model = new Schedule();

            $model->game = $gameType;
            $model->number = $number;
            $model->status = GameStatus::NEW;
            $model->send_status = SendStatus::IN_QUEUE;

            $model->save();
        }

        return $model;
    }

    public static function getActiveGameNumbers(GameType $gameType): array
    {
        $numbers = [];
        $games = Schedule::where('game', $gameType)->where('status', GameStatus::NEW)->get();

        foreach ($games as $game) {
            $numbers[] = $game->number;
        }

        return $numbers;
    }

    public static function setFinished(GameType $gameType, int $number): void
    {
        $model = Schedule::getGameByTypeAndNumber($gameType, $number);

        $model->status = GameStatus::FINISHED;

        $model->save();
    }

    public static function setInProgress(GameType $gameType, int $number): void
    {
        $model = Schedule::getGameByTypeAndNumber($gameType, $number);

        $model->status = GameStatus::IN_PROGRESS;

        $model->save();
    }

    public static function getGamesInProgress(GameType $gameType): Collection
    {
        return Schedule::where('game', $gameType)->where('status', GameStatus::IN_PROGRESS)->get();
    }

    public static function getInQueue(): Collection
    {
        return Schedule::where('send_status', SendStatus::IN_QUEUE)->get();
    }

    public static function setSend(Schedule $schedule): void
    {
        $schedule->send_status = SendStatus::SEND;
        $schedule->save();
    }
}
