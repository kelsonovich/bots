<?php

namespace App\Service\Parsing;

use App\Enum\GameType;
use Illuminate\Support\Facades\Http;
use App\Link\Mozgva as MozgvaLink;

class Albums
{
    public static function start(GameType $gameType, string $groupId): void
    {
        $token = env('VK_TOKEN');
        $params = [
            'owner_id' => $groupId,
            'count' => 10,
            'access_token' => $token,
            'v' => '5.103'
        ];

        $response = Http::get(env('VK_ALBUM_API'), $params);

        dd($response->json());
    }
}
