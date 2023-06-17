<?php

namespace App\Service\Parsing\Mozgva;

use App\Enum\GameType;
use App\Link\Mozgva as MozgvaLink;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\Rating as RatingModel;
use voku\helper\HtmlDomParser;

class TeamList
{
    private static \Illuminate\Database\Eloquent\Collection $rating;

    public static function start(): void
    {
        $start = new Carbon(tz: 'Europe/Moscow');
        $games = \App\Models\Schedule::where('game', GameType::MOZGVA)->where('start', '>=', $start)->get();

        foreach ($games as $game) {
            $link = str_replace('#GAME_ID#', $game->number, MozgvaLink::TEAM_LIST);
//            $page = file_get_contents($link);

            dd($link, $page ?? '');
        }

//        $response = Http::get(MozgvaLink::TEAM_LIST);

//        if ($response->status() === 200) {
//            $rating = $response->json();
//
//            self::$rating = RatingModel::getMozgvaRating();
//
//            foreach ($rating['top'] as $page) {
//                foreach ($page as $team) {
//                    self::createOrUpdate($team);
//                }
//
//                break;
//            }
//        }
    }

    private static function createOrUpdate(array $team): void
    {
//        if (self::$rating->count() === 0) {
//            $model = new RatingModel();
//
//            $model->game = GameType::MOZGVA;
//            $model->position = $team['position'];
//
//            $model->save();
//        } else {
//            $model = self::$rating->firstWhere('position', $team['position']);
//        }
//
//        $model->position = $team['position'];
//        $model->team = $team['name'];
//        $model->points = $team['scores'];
//        $model->percent = $team['percent'];
//        $model->count_games = $team['games_count'];
//
//        $model->save();
    }
}
