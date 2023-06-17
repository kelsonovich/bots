<?php

namespace App\Service;

use App\Enum\GameStatus;
use App\Enum\GameType;
use App\Models\PackageResult;
use App\Models\Result;
use App\Models\Schedule;

class PackageWinnerService
{
    private static array $medals = ['🥇', '🥈', '🥉'];

    private static array $packageSchedules = [];

    public static function start(): void
    {
        $packages = PackageResult::getNew();

        foreach ($packages as $package) {
            if (self::isOver($package)) {
                self::setPackageWinner($package);
            }
        }

//        dd($packages);
    }

    private static function isOver(PackageResult $packageResult): bool
    {
        $titleAsArray = explode(' ', $packageResult->package);

        $package = (int) array_pop($titleAsArray);
        $title = implode(' ', $titleAsArray);

        self::$packageSchedules[$packageResult->package] = Schedule::where([
            'game' => $packageResult->game,
            'package' => $package,
            'title' => $title,
        ])->get();

        return self::$packageSchedules[$packageResult->package]->filter(function ($schedule) {
                return $schedule->status === GameStatus::NEW->value;
            })->count() === 0;
    }

    private static function setPackageWinner(PackageResult $packageResult): void
    {
        $results = [];
        $schedules = self::$packageSchedules[$packageResult->package]->filter(function ($schedule) {
            return $schedule->status === GameStatus::FINISHED->value;
        });

        if ($schedules->count() > 0) {
            foreach ($schedules as $schedule) {
                $result = Result::where(['game' => $schedule->game, 'number' => $schedule->number])->first();

                $currentResults = json_decode($result->results, true);

                if ($schedule->game === GameType::QUIZPLEASE->value) {
                    $currentResults = array_slice($currentResults, 0, 5);
                }

                $results = array_merge($results, $currentResults);
            }

            $sortOrder = [];

            if ($schedules->first()->game === GameType::MOZGVA->value) {
                $sortOrder = [9, 8, 5, 7, 2, 3, 4, 6];

            } elseif ($schedules->first()->game === GameType::QUIZPLEASE->value) {
                $length = count($results[0]);

                foreach (range(1, ($length - 2)) as $round) {
                    $sortOrder[] = ($length - $round);
                }
            }

            foreach ($sortOrder as $item) {
                $sort[] = fn (array $a, array $b) => $a[$item] <=> $b[$item];
            }

            $results = collect($results)->sortBy($sort);

            $packageResult->results = array_reverse($results->toArray());
            $packageResult->status = GameStatus::FINISHED;
            $packageResult->save();
        }
    }
}
