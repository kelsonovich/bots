<?php

namespace App\Service\Parsing\Mozgva;

use App\Enum\GameType;
use App\Enum\GameStatus;
use App\Enum\SendStatus;
use App\Link\Mozgva as MozgvaLink;
use App\Models\Result as ResultModel;
use App\Models\Schedule as ScheduleModel;
use App\Models\Tables as TableModel;
use App\Service\Parsing\ParsingService;
use Carbon\Carbon;
use voku\helper\HtmlDomParser;

class Table
{
    private static GameType $type = GameType::MOZGVA;

    public static function start(): void
    {
        foreach (ScheduleModel::getGamesInProgress(self::$type) as $schedule) {
            self::createOrUpdate($schedule);
        }
    }

    private static function createOrUpdate(ScheduleModel $schedule): void
    {
        if (! $schedule || $schedule->status === GameStatus::FINISHED->value) {
            return;
        }

        $result = TableModel::getResultByNumberAndQuiz(self::$type, $schedule->number);

        if (self::isFinished($schedule->number)) {
            $result->status = GameStatus::FINISHED;
            $result->send_status = SendStatus::IN_QUEUE;

            ScheduleModel::setFinished(self::$type, $schedule->number);
        }

        $result->save();

    }

    private static function isFinished(int $number): bool
    {
        $page = HtmlDomParser::file_get_html(str_replace('#GAME_ID#', $number, MozgvaLink::GAME));

        if (count($page->find('.team_body')) === 0) {
            return false;
        }

        $firstTeamRound = $page->find('.team_body .tour_7', 0);

        if (($firstTeamRound && strlen($firstTeamRound->plaintext) > 0)) {

            $results = [];
            foreach ($page->find('.team_body_wrapper .team_body') as $row) {
                $position = (int) $row->find('.pos', 0)->plaintext;
                $team     = $row->find('.team_and_tour .name', 0)->plaintext;
                $total    = (int) $row->find('.sum', 0)->plaintext;

                $points = array_map('intval', $row->find('.tour')->plaintext);

                $results[] = array_merge(
                    [$position],
                    [$team],
                    $points,
                    [$total],
                );
            }

            $results = array_slice($results, 0, 5);

            ResultModel::setResults(self::$type, $number, $results);

            return true;
        }

        return false;
    }
}


