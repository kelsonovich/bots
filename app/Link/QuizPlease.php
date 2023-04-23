<?php

namespace App\Link;

/** Класс для хранения всяких ссылок */
class QuizPlease
{
    const API_SCHEDULE = 'https://quizplease.ru/api/game?city_id=9';
    const API_RATING   = 'https://quizplease.ru/api/rating?QpRaitingSearch[general]=1';

    const VG_GROUP_ID  = '-114998728';
    const SCHEDULE     = 'https://moscow.quizplease.ru/schedule';
    const RESULT       = 'https://moscow.quizplease.ru/schedule-past';
    const GAME         = 'https://moscow.quizplease.ru/game-page?id=';

    const TITLE = [
        'RESULT' => 'КВИЗ, ПЛИЗ!'
    ];
}
