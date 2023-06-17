<?php

namespace App\Service\Parsing\Mozgva;

use App\Enum\GameType;
use App\Enum\SendStatus;
use App\Link\Mozgva as MozgvaLink;
use App\Models\PackageResult;
use App\Service\Parsing\ParsingSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\Schedule as ScheduleModel;

class Schedule extends ParsingSchedule
{
    protected static GameType $type = GameType::MOZGVA;

    public static function start(): void
    {
        $response = Http::get(MozgvaLink::API_SCHEDULE);

        self::$allGameNumbers = ScheduleModel::getActiveGameNumbers(self::$type);

        if ($response->status() === 200) {
            foreach ($response->json() as $game) {
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
            $model->package = self::getPackage($game['GAME_NAME']);
            $model->title = self::getTitle($game['GAME_NAME'], $model->package);
            $model->full_title = trim($game['GAME_NAME']);
            $model->price = $game['PRICE'];
            $model->place = $game['PLACE'];
            $model->start = Carbon::createFromFormat('H:i d.m.Y', $game['TIME'] . ' ' . $game['DATE']);
        }

        if ($model->package > 0) {
            PackageResult::createPackage(self::$type, ($model->title . ' ' . $model->package));
        }

        $model->save();
    }

    private static function getPackage(string $title): int
    {
        $title = mb_strtolower($title);

        if (mb_stripos($title, 'день')) {
            $title = mb_substr($title, 0, mb_stripos($title, 'день'));
        }

        $title = str_replace(['+ диско'], '', $title);
        $title = trim($title);

        $titleAsArray = explode(' ', $title);

        return (int) end($titleAsArray);
    }

    private static function isNew(ScheduleModel $model, array $game): bool
    {
        if (strlen($model->title) === 0) {
            return true;
        }

        $modelHash = static::getHash([$model->title, $model->price, $model->place, $model->start->format('H:i d.m.Y')]);

        $gameHash = static::getHash([
            $game['GAME_NAME'], $game['PRICE'], $game['PLACE'], $game['TIME'] . ' ' . $game['DATE']
        ]);

        return ($modelHash !== $gameHash);
    }

    private static function getTitle(string $title, int $package): string
    {
        if ($package === 0) {
            return trim($title);
        }

        $position = mb_stripos($title, $package);

        if ($position) {
            $title = mb_substr($title, 0, $position);
        }

        return trim($title);
    }
}
