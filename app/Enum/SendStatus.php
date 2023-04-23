<?php

namespace App\Enum;

enum SendStatus: string
{
    case IN_QUEUE = 'in_queue';
    case SEND     = 'send';
}
