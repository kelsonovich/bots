<?php

namespace App\Service\Response;

use App\Enum\GameType;
use App\Models\Rating;
use App\Models\Schedule;
use Carbon\Carbon;
use App\Link\Mozgva as MozgvaLink;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

class Mozgva
{
    const START     = '/start';
    const SCHEDULE  = 'Расписание игр';
    const ALBUMS    = 'Фотографии';
    const RATING    = 'Рейтинг';
    const RESULTS   = 'Результаты';
    const TABLE     = 'Онлайн табличка';

    const ADMIN_RESULT = 'Спойлеры';
    const ADMIN_TEAM_LIST = 'Списки команд';

    const COMMANDS = [
        Mozgva::START,
        Mozgva::SCHEDULE,
        Mozgva::ALBUMS,
        Mozgva::RATING,
        Mozgva::RESULTS,
        Mozgva::TABLE,
    ];

    const BUTTONS = [
        [Mozgva::SCHEDULE, Mozgva::RATING],
        [Mozgva::RESULTS, Mozgva::TABLE],
    ];

    const ADMIN_BUTTONS = [
        [Mozgva::ADMIN_TEAM_LIST, Mozgva::ADMIN_RESULT],
    ];

    const COMMAND_NOT_ALLOWED = 'Данная опция отключена организатором игры.';
    const COMMAND_TEMPORARY_NOT_WORKING = 'Данная опция временно не работает.';
    const COMMAND_WELCOME = [
        'Добро пожаловать в бота!',
        'Могу показать результаты игр, онлайн табличку и расписание игр.',
    ];
    const COMMAND_UNKNOWN = 'Неизвестная команда. Воспользуйся клавиатурой';

    public static function execute(string $message, bool $isAdmin = false): array
    {
        $response = [];

        if ($isAdmin) {
            if ($message === Mozgva::ADMIN_RESULT) {
                $response = self::prepare(self::table($isAdmin));
            } else if ($message === Mozgva::ADMIN_TEAM_LIST) {
                [$response, $inlineButtons] = self::teamList($isAdmin);
                $response = self::prepare($response);
            }

            if ($response) {
                return [$response, $inlineButtons ?? []];
            }
        }

        switch ($message) {
            case (Mozgva::START):     $response = Mozgva::COMMAND_WELCOME; break;
            case (Mozgva::SCHEDULE):  $response = self::prepare(self::schedule()); break;
            case (Mozgva::ALBUMS):    $response = [Mozgva::COMMAND_TEMPORARY_NOT_WORKING]; break;
            case (Mozgva::RATING):    $response = self::prepare(self::rating()); break;
            case (Mozgva::RESULTS):   $response = self::prepare(self::results()); break;
            case (Mozgva::TABLE):     $response = self::prepare(self::table()); break;
            default:                  $response = [Mozgva::COMMAND_UNKNOWN]; break;
        }

        return [$response, []];
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

    private static function teamList(): array
    {
        $schedules = Schedule::where('game', GameType::MOZGVA)
            ->where('start', '>', (new Carbon(tz: 'Europe/Moscow')))
            ->orderBy('start', 'ASC')
//            ->limit(10)
            ->get();

        $message = [];
        $message[] = 'Ссылки на списки команд:';
        $keyboard = Keyboard::make();
        foreach ($schedules->chunk(2) as $row) {
            $inlineButtons = [];

            foreach ($row as $schedule) {
                $newTitle = str_replace('Мозгва', '', $schedule->title);

                $title = [];
                foreach(explode(' ', $schedule->full_title) as $part) {
                    if (is_numeric($part)) {
                        $title[] = $part;
                    }
                }

                $inlineButtons[] = Button::make($newTitle . ' ' . implode('.', $title))->url(
                    str_replace('#GAME_ID#', $schedule->number, MozgvaLink::TEAM_LIST)
                );
            }

            $keyboard->row($inlineButtons);
        }

//        $keyboard->row([
//            Button::make('⬅️ Назад')->action('back')->param('page', '0'),
//            Button::make('➡️ Вперед')->action('forward')->param('page', '2'),
//            Button::make('switch')->switchInlineQuery('foo')->currentChat(),
//        ]);


        return [$message, $keyboard];
    }

    private static function table(bool $isAdmin = false): array
    {
        $date = (new Carbon(tz: 'Europe/Moscow'));

        $results = Schedule::where('game', GameType::MOZGVA)
            ->whereBetween('start', [ $date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderBy('start', 'ASC')
            ->get();

        $message = [];

        if ($isAdmin) {
            $message[] = "Таблица со спойлерами:\n";
            $link = MozgvaLink::SPOILER_GAME;

        } else {
            $link = MozgvaLink::GAME;
            $message[] = "Онлайн таблицы:\n";
        }

        foreach ($results as $result) {
            $message[] = self::getLink($result, $link);
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

    private static function getLink(Schedule $schedule, string $link = MozgvaLink::GAME): string
    {
        return '<a href="' . str_replace('#GAME_ID#', $schedule->number, $link) . '"> '
            . $schedule->full_title . '</a>';
    }

    private static function prepare(array $messageAsArray): array
    {
        return [implode("\n", $messageAsArray)];
    }

    public static function isAdmin(array $webhook): bool
    {
        return in_array($webhook['message']['from']['username'], ['amadeus_vult', 'Bugsyro', 'amadeus3000']);
    }
}