<?php

namespace App\Helper;

use App\Models\Schedule;
use Carbon\Carbon;

class Helper
{
    public static function getScheduleAsTelegramMessage(Schedule $schedule): array
    {
        $message = [];
        $message[] = $schedule->full_title;
        $message[] = trim($schedule->place) . ' ' . $schedule->price . '₽';

        $date = (new Carbon($schedule->start, 'Europe/Moscow'))->addHours(-3)->locale('ru');
        $message[] =
            $date->format('j')
            . ' ' .  mb_convert_case($date->getTranslatedMonthName('Do MMMM'), MB_CASE_TITLE)
            . ', ' . mb_convert_case($date->getTranslatedDayName('Do MMMM'), MB_CASE_TITLE)
            . ' в ' . $date->format('H:i');

        $message[] = '';

        return $message;
    }
}
