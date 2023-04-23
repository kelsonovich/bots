<?php

namespace App\Models;

use App\Enum\GameType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rating extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'game',
        'position',
        'team',
        'points',
        'percent',
        'count_games',
        'count_games',
        'status',
        'send_status',
    ];

    public static function getMozgvaRating(): \Illuminate\Database\Eloquent\Collection
    {
        return Rating::where('game', GameType::MOZGVA)->orderBy('position', 'ASC')->get();
    }
}
