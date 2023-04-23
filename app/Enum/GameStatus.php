<?php

namespace App\Enum;

enum GameStatus: string
{
    case FINISHED    = 'finished';
    case IN_PROGRESS = 'in_progress';
    case NEW         = 'new';
    case CANCELED    = 'canceled';
}
