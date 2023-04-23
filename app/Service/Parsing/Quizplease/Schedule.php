<?php

namespace App\Service\Parsing\Quizplease;

use App\Enum\GameType;
use App\Enum\SendStatus;
use App\Link\QuizPlease as QuizPleaseLink;
use App\Models\PackageResult;
use App\Service\Parsing\ParsingSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\Schedule as ScheduleModel;

class Schedule extends ParsingSchedule
{
    protected static GameType $type = GameType::QUIZPLEASE;

    public static function start(): void
    {
        $response = Http::get(QuizPleaseLink::API_SCHEDULE);

        self::$allGameNumbers = ScheduleModel::getActiveGameNumbers(self::$type);

        if ($response->status() === 200) {
            $games = $response->json()['data']['data'];

            foreach ($games as $game) {
                self::createOrUpdate($game);

                self::$actualGameNumbers[] = $game['id'];
            }

            self::removeGames(self::$type);
        }
    }

    private static function createOrUpdate(array $game): void
    {
        $model = ScheduleModel::getGameByTypeAndNumber(self::$type, $game['id']);

        if (self::isNew($model, $game)) {
            $model->title = trim($game['title']);
            $model->package = self::getPackage($game['name']);
            $model->full_title = self::getFullTitle($model);
            $model->price = $game['price'];
            $model->place = $game['place'];
            $model->start = Carbon::createFromFormat('d.m.y H:i', $game['datetime']);
            $model->send_status = SendStatus::IN_QUEUE;
        }

        if ($model->package > 0) {
            PackageResult::createPackage(self::$type, ($model->title . ' ' . $model->package));
        }

        $model->save();
    }

    private static function isNew(ScheduleModel $model, array $game): bool
    {
        if (strlen($model->title) === 0) {
            return true;
        }

        $modelHash = static::getHash([$model->title, $model->price, $model->place, $model->start->format('d.m.y H:i')]);

        $gameHash = static::getHash([
            $game['title'], $game['price'], $game['place'], $game['datetime']
        ]);

        return ($modelHash !== $gameHash);
    }

    private static function getPackage(string $package): int
    {
        $package = str_replace(['#'], '', $package);

        return (int) trim($package);
    }

    private static function getFullTitle(ScheduleModel $model): string
    {
        $fullTitle = $model->title;

        if ($model->package !== 0) {
            $fullTitle .= ' #' . $model->package;
        }

        return $fullTitle;
    }
}
