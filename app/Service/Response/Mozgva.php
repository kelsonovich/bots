<?php

namespace App\Service\Response;

use App\Enum\GameType;
use App\Models\Rating;
use App\Models\Schedule;
use Carbon\Carbon;
use App\Link\Mozgva as MozgvaLink;

class Mozgva
{
    const START     = '/start';
    const SCHEDULE  = 'Расписание игр';
    const TEAM_LIST = 'Списки команд';
    const ALBUMS    = 'Фотографии';
    const RATING    = 'Рейтинг';
    const RESULTS   = 'Результаты';
    const TABLE     = 'Онлайн табличка';

    const COMMANDS = [
        Mozgva::START,
        Mozgva::SCHEDULE,
        Mozgva::TEAM_LIST,
        Mozgva::ALBUMS,
        Mozgva::RATING,
        Mozgva::RESULTS,
        Mozgva::TABLE,
    ];

    const BUTTONS = [
        [Mozgva::SCHEDULE, Mozgva::TEAM_LIST],
        [Mozgva::ALBUMS, Mozgva::RATING],
        [Mozgva::RESULTS, Mozgva::TABLE],
    ];

    const COMMAND_NOT_ALLOWED = 'Данная опция отключена организатором игры.';
    const COMMAND_TEMPORARY_NOT_WORKING = 'Данная опция временно не работает.';
    const COMMAND_WELCOME = [
        'Добро пожаловать в бота!',
        'Могу показать результаты игр, онлайн табличку и расписание игр.',
    ];
    const COMMAND_UNKNOWN = 'Неизвестная команда. Воспользуйся клавиатурой';

    public static function execute(string $message): array
    {
        switch ($message) {
            case (Mozgva::START):     return Mozgva::COMMAND_WELCOME;
            case (Mozgva::SCHEDULE):  return self::prepare(self::schedule());
            case (Mozgva::TEAM_LIST): return [Mozgva::COMMAND_NOT_ALLOWED];
            case (Mozgva::ALBUMS):    return [Mozgva::COMMAND_TEMPORARY_NOT_WORKING];
            case (Mozgva::RATING):    return self::prepare(self::rating());
            case (Mozgva::RESULTS):   return self::prepare(self::results());
            case (Mozgva::TABLE):     return self::prepare(self::table());
            default:                  return [Mozgva::COMMAND_UNKNOWN];

        }

        return [];
    }

    private static function schedule(): array
    {
        $schedules = Schedule::where('game', GameType::MOZGVA)
            ->where('start', '>', (new Carbon(tz: 'Europe/Moscow')))
            ->orderBy('start', 'ASC')
            ->get();

        $message = [];
        foreach ($schedules as $schedule) {
            $message[] = $schedule->full_title;
            $message[] = trim($schedule->place) . ' ' . $schedule->price . '₽';

            $date = (new Carbon($schedule->start, 'Europe/Moscow'))->addHours(-3)->locale('ru');
            $message[] =
                $date->format('j')
                . ' ' .  mb_convert_case($date->getTranslatedMonthName('Do MMMM'), MB_CASE_TITLE)
                . ', ' . mb_convert_case($date->getTranslatedDayName('Do MMMM'), MB_CASE_TITLE)
                . ' в ' . $date->format('H:i');

            $message[] = '';
        }

        return $message;
    }

    private static function table(): array
    {
        $date = (new Carbon(tz: 'Europe/Moscow'));

        $results = Schedule::where('game', GameType::MOZGVA)
            ->whereBetween('start', [ $date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderBy('start', 'ASC')
            ->get();

        $message = [];
        $message[] = "Онлайн таблицы:\n";
        foreach ($results as $result) {
            $message[] = self::getLink($result);
        }

        return $message;
    }

    private static function rating(): array
    {
        $rating = Rating::getMozgvaRating();

        $message = [];
        $message[] = "Рейтинг:\n";
        foreach ($rating as $team) {
            $message[] = $team->position . '. ' .  $team->percent . '% ' .  $team->team;
        }

        return $message;
    }

    private static function results(): array
    {
        $results = Schedule::where('game', GameType::MOZGVA)
            ->where('start', '<', (new Carbon(tz: 'Europe/Moscow')))
            ->orderBy('start', 'DESC')
            ->limit(10)
            ->get();

        $message = [];
        $message[] = "Результаты игр:\n";
        foreach ($results as $result) {
            $message[] = self::getLink($result);
        }

        return $message;
    }

    private static function getLink(Schedule $schedule): string
    {
        return '<a href="' . str_replace('#GAME_ID#', $schedule->number, MozgvaLink::GAME) . '"> ' . $schedule->full_title . '</a>';
    }

    private static function prepare(array $messageAsArray): array
    {
        return [implode("\n", $messageAsArray)];
    }
}