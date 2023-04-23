<?php

namespace App\Models;

use App\Enum\GameStatus;
use App\Enum\GameType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tables extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'game',
        'position',
        'status',
        'send_status',
    ];

    public static function getResultByNumberAndQuiz(GameType $game, int $number): Tables
    {
        $result = Tables::where('game', $game)->where('number', $number)->first();

        if (! $result) {
            $result = new Tables();

            $result->game = $game;
            $result->number = $number;
            $result->status = GameStatus::NEW;

            $result->save();
        }

        return $result;
    }
}
