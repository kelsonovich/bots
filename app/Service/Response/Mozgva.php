<?php

namespace App\Service\Response;

use App\Enum\GameType;
use App\Helper\Helper;
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
        $inlineButtons = [];

        if ($isAdmin) {
            if ($message === Mozgva::ADMIN_RESULT) {
                $response = self::prepare(self::table($isAdmin));
            } else if ($message === Mozgva::ADMIN_TEAM_LIST) {
                [$response, $inlineButtons] = self::teamList();
                $response = self::prepare($response);
            }

            if ($response) {
                return [$response, $inlineButtons ?? []];
            }
        }

        switch ($message) {
            case (Mozgva::START):     $response = Mozgva::COMMAND_WELCOME; break;
            case (Mozgva::SCHEDULE):
                [$response, $inlineButtons] = self::schedule();
                $response = self::prepare($response);
                break;
            case (Mozgva::ALBUMS):    $response = [Mozgva::COMMAND_TEMPORARY_NOT_WORKING]; break;
            case (Mozgva::RATING):    $response = self::prepare(self::rating()); break;
            case (Mozgva::RESULTS):   $response = self::prepare(self::results()); break;
            case (Mozgva::TABLE):     $response = self::prepare(self::table()); break;
            default:                  $response = [Mozgva::COMMAND_UNKNOWN]; break;
        }

        return [$response, $inlineButtons];
    }

    public static function schedule(int $page = 1): array
    {
        $limit = 6;
        $offset = ($page - 1) * $limit;

        $schedules = Schedule::where('game', GameType::MOZGVA)
            ->where('start', '>', (new Carbon(tz: 'Europe/Moscow')))
            ->orderBy('start', 'ASC');

        $totalCount = $schedules->count();

        $schedules = $schedules->offset($offset)->limit(5)->get();

        $message = [];
        $message[] = "<b>Расписание игр:</b>\n";
        $keyboard = Keyboard::make();
        foreach ($schedules as $schedule) {
            $message = array_merge($message, Helper::getScheduleAsTelegramMessage($schedule));
        }

        $paginate = [];

        if ($page >= 2) {
            $paginate[] = Button::make('⬅️ Назад')->action('schedule')->param('page', ($page - 1));
        }

        if ($page * $limit < $totalCount) {
            $paginate[] = Button::make('Вперед ➡️')->action('schedule')->param('page', ($page + 1));
        }

        if (count($paginate) > 0) {
            $keyboard->row($paginate);
        }

        return [$message, $keyboard];
    }

    public static function teamList(int $page = 1): array
    {
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $schedules = Schedule::where('game', GameType::MOZGVA)
            ->where('start', '>', (new Carbon(tz: 'Europe/Moscow')))
            ->orderBy('start', 'ASC');

        $totalCount = $schedules->count();

        $schedules = $schedules->offset($offset)->limit($limit)->get();

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

        $paginate = [];

        if ($page >= 2) {
            $paginate[] = Button::make('⬅️ Назад')->action('teamList')->param('page', ($page - 1));
        }

        if ($page * $limit < $totalCount) {
            $paginate[] = Button::make('Вперед ➡️')->action('teamList')->param('page', ($page + 1));
        }

        if (count($paginate) > 0) {
            $keyboard->row($paginate);
        }

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

    public static function prepare(array $messageAsArray): array
    {
        return [implode("\n", $messageAsArray)];
    }

    public static function isAdmin(array $webhook): bool
    {
        return in_array($webhook['from']['username'], ['amadeus_vult', 'Bugsyro', 'amadeus3000']);
    }
}
