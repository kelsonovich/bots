<?php

namespace App\Service\Parsing;

use App\Enum\GameType;
use App\Models\Schedule as ScheduleModel;

class ParsingSchedule
{
    protected static array $allGameNumbers;
    protected static array $actualGameNumbers;

    protected static function getHash(array $params): string
    {
        return md5(implode(' - ', $params));
    }

    protected static function removeGames(GameType $gameType): void
    {
        foreach (array_diff(self::$allGameNumbers, self::$actualGameNumbers) as $gameNumber) {
            ScheduleModel::setInProgress($gameType, $gameNumber);
        }
    }
}
