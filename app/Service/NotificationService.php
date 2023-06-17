<?php

namespace App\Service;

use App\Enum\GameType;
use App\Link\Mozgva;
use App\Link\QuizPlease;
use App\Models\Notification;
use App\Models\PackageResult;
use App\Models\Result;
use App\Models\Schedule;
use Carbon\Carbon;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;

class NotificationService
{
    private static array $messages = [];
    private static array $medals = ['🥇', '🥈', '🥉'];

    public static function prepare(): void
    {
        self::setResults();
        self::setSchedule();
        self::setPackageWinners();

        foreach (self::$messages as $message) {
            Notification::create([
                'text' => $message,
            ]);
        }
    }

    public static function send(): void
    {
        $chat = TelegraphChat::where('name', env('TG_NOTIFICATION_CHAT_NAME'))->first();

        foreach (Notification::getInQueue() as $notification) {
            $chat->html($notification->text)
                ->withoutPreview()
                ->silent()
                ->send();

            Notification::setSend($notification);

            usleep(500 * 1000);
        }
    }

    private static function setResults():void
    {
        $results = Result::getInQueue();

        foreach ($results as $result) {
            $game = Schedule::getGameByTypeAndNumber(GameType::from($result->game), $result->number);

            $table = json_decode($result->results, true);

            $table = array_slice($table, 0, 3);

            $message = [];

            $game = "<b>Результаты " . self::getGameTitle($game->game) . ":</b> \n"
                . trim(str_replace(["\n"], '', self::getGameLink($game)));

            $maxLength = 0;
            foreach ($table as $row) {
                $length = strlen((string) end($row));
                $maxLength = ($maxLength < $length) ? $length : $maxLength;
            }

            foreach ($table as $key => $row) {
                $total = end($row);

                $total = $total . str_repeat(' ', ($maxLength  - strlen((string) $total)));

                $message[] = implode(' ', [self::$medals[$key], $total, $row[1]]);
            }

            self::$messages[] = $game . "\n\n"  . trim(implode("\n", $message));

            Result::setSend($result);
        }
    }

    private static function setSchedule():void
    {
        $schedules = Schedule::getInQueue();
        foreach ($schedules as $schedule) {
            $message = [];

            $message[] = 'Новая игра от ' . self::getGameTitle($schedule->game) . ':';
            $message[] = $schedule->full_title;
            $message[] = trim($schedule->place) . ' ' . $schedule->price . '₽';

            $date = (new Carbon($schedule->start, 'Europe/Moscow'))->addHours(-3)->locale('ru');
            $message[] =
                $date->format('j')
                . ' ' .  mb_convert_case($date->getTranslatedMonthName('Do MMMM'), MB_CASE_TITLE)
                . ', ' . mb_convert_case($date->getTranslatedDayName('Do MMMM'), MB_CASE_TITLE)
                . ' в ' . $date->format('H:i');


            self::$messages[] = implode("\n", $message);

            Schedule::setSend($schedule);
        }
    }

    private static function setPackageWinners(): void
    {
        $results = PackageResult::getFinished();
        foreach ($results as $result) {
            $table = array_slice(json_decode($result->results, true), 0, 3);

            $maxLength = 0;
            foreach ($table as $row) {
                $length = strlen((string) end($row));
                $maxLength = ($maxLength < $length) ? $length : $maxLength;
            }

            $message = [];
            $message[] = 'Результаты пакета <b>' . $result->package . '</b>:' . "\n";
            foreach ($table as $key => $row) {
                $total = end($row);

                $total = $total . str_repeat(' ', ($maxLength  - strlen((string) $total)));

                $message[] = implode(' ', [self::$medals[$key], $total, $row[1]]);
            }

            self::$messages[] = implode("\n", $message);

            PackageResult::setSend($result);
        }
    }

    private static function getGameLink(Schedule $game): string
    {
        if ($game->game === GameType::MOZGVA->value) {
            $link = str_replace('#GAME_ID#', $game->number, Mozgva::GAME);
        } elseif ($game->game === GameType::QUIZPLEASE->value) {
            $link = QuizPlease::GAME . $game->number;
        }

        return '<a href="' . $link . '">' . $game->full_title . '</a>';
    }

    private static function getGameTitle(string $dameType): string
    {
        return ($dameType === GameType::MOZGVA->value) ? Mozgva::TITLE['RESULT'] : QuizPlease::TITLE['RESULT'];
    }
}
