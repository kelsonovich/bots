<?php

namespace App\Service\Parsing\Quizplease;

use App\Enum\GameStatus;
use App\Enum\GameType;
use App\Enum\SendStatus;
use App\Link\QuizPlease as QuizPleaseLink;
use App\Models\Notification;
use App\Models\Schedule as ScheduleModel;
use App\Models\Result as ResultModel;
use App\Models\Tables as TableModel;
use voku\helper\HtmlDomParser;

class Table
{
    private static array $page = [];

    private static GameType $type = GameType::QUIZPLEASE;

    public static function start(): void
    {
        foreach (ScheduleModel::getGamesInProgress(self::$type) as $schedule) {
            self::createOrUpdate($schedule);
        }
    }

    private static function createOrUpdate(ScheduleModel $schedule): void
    {
        $result = TableModel::getResultByNumberAndQuiz(self::$type, $schedule->number);

        if (self::isFinished($schedule->number)) {
            $result->status = GameStatus::FINISHED->value;
            $result->send_status = SendStatus::IN_QUEUE;

            ScheduleModel::setFinished(self::$type, $schedule->number);
        }

        $result->save();
    }

    private static function isFinished(int $number): bool
    {
        if (! array_key_exists($number, self::$page)) {
            self::$page[$number] = HtmlDomParser::file_get_html(QuizPleaseLink::GAME . $number);
        }

        $isFinished = false;

        $rows = self::$page[$number]->find('table.game-table tr');

        if (count($rows) > 0) {
            self::sendGameWasStarted($number);
        }

        foreach ($rows as $key => $row) {
            if ($key === 0) {
                $numberLastRound = self::getLastRoundNumber($row);

                continue;
            }

            if (($numberLastRound ?? 0) === 0 ) {
                break;
            }

            $round = $row->find('td')[$numberLastRound - 1]->plaintext;

            if (strlen($round) > 0) {
                if ((int) $round > 0) {
                    $isFinished = true;

                    self::setResults($rows, $number);

                    break;
                }
            }
        }

        return $isFinished;
    }

    private static function getLastRoundNumber(object $row): int
    {
        $number = 0;

        foreach ($row->find('td') as $key => $td) {
            $cellTitle = trim(mb_strtolower($td->plaintext));

            foreach (['round', 'раунд'] as $title) {
                if (mb_stripos($cellTitle, $title) >= 0) {
                    $number = $key;
                }
            }
        }

        return $number;
    }

    private static function setResults(object $rows, int $number): void
    {
        $useless = ['оскар', 'город', 'ранг', 'rank', 'city', 'oscar'];
        $skipKeys = [];
        foreach ($rows[0]->find('td') as $key => $td) {
            if (in_array(mb_strtolower($td->plaintext), $useless)) {
                $skipKeys[] = $key;
            }
        }

        $results = [];

        foreach ($rows as $row) {
            $team = [];
            foreach ($row->find('td') as $key => $td) {
                if (! in_array($key, $skipKeys)) {
                    $team[] = $td->plaintext;
                }
            }

            $results[] = $team;
        }

        array_shift($results);

        ResultModel::setResults(self::$type, $number, $results);
    }

    private static function sendGameWasStarted(int $number): void
    {
        $schedule = ScheduleModel::where('number', $number)->where('game', self::$type)->first();

        $message = [];

        $message[] = 'Началась игра <b>' . $schedule->full_title . "</b>:";
        $message[] = QuizPleaseLink::GAME . $schedule->number;

        $message = implode("\n", $message);

        Notification::firstOrCreate(['text' => $message]);
    }
}
