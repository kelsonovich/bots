<?php

namespace App\Link;

/** Класс для хранения всяких ссылок */
class Mozgva
{
    const API_RATING      = 'https://mozgva.com/api/v1/teams/rating?city_id=1&thematic_rating_id=1';
    const API_SCHEDULE    = 'https://mozgva.com/api/v1/games/schedule?id=1';
    const API_SEND_SECRET = 'https://mozgva.com/api/v1/teams/21756/send_secret';

    const RESULT       = 'https://mozgva.com/online?city_id=1';
    const SCHEDULE     = 'https://mozgva.com/calendar?city_id=1';
    const GAME         = 'https://mozgva.com/games/#GAME_ID#/report';

    const VK_GROUP_ID  = '-95512899';

    const TITLE = [
        'RESULT' => 'Мозгвы'
    ];
}
