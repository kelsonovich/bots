<?php

namespace App\Service\Parsing;

use App\Service\Parsing\Mozgva\Schedule as MozgvaSchedule;
use App\Service\Parsing\Mozgva\Rating as MozgvaRating;
use App\Service\Parsing\Mozgva\Table as MozgvaTable;
use App\Service\Parsing\Mozgva\TeamList as MozgvaTeamList;
use App\Service\Parsing\Quizplease\Schedule as QuizpleaseSchedule;
use App\Service\Parsing\Quizplease\Table as QuizpleaseTable;

class ParsingService
{
    public static function mozgva(): void
    {
        MozgvaTable::start();
        MozgvaRating::start();
        MozgvaSchedule::start();

        /** Не работает */
//        Albums::start(GameType::MOZGVA, MozgvaLink::VK_GROUP_ID);
    }

    public static function quizplease(): void
    {
        QuizpleaseTable::start();
        QuizpleaseSchedule::start();
    }

    public static function test(): void
    {
        MozgvaTeamList::start();
    }
}
