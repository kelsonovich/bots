<?php

namespace App\Http\Controllers\Mozgva;

use App\Models\BotUsageLog;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Service\Response\Mozgva;

class MozgvaController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    private TelegraphChat $chat;

    public function webhook(Request $request): void
    {
        $webhook = $request->json()->all();

        $this->setChat($webhook);
        $message = $webhook['message']['text'];

        if ((int) $webhook['message']['from']['id'] === 252276645) {

        } else {

        }

        $response = Mozgva::execute($message);

        $keyboard = ReplyKeyboard::make();
        foreach (\App\Service\Response\Mozgva::BUTTONS as $row) {
            $buttons = [];
            foreach ($row as $button) {
                $buttons[] = ReplyButton::make($button);
            }

            $keyboard = $keyboard->row($buttons);
        }

        foreach ($response as $responseMessage) {
            $this->chat->html($responseMessage)
                ->replyKeyboard($keyboard->resize())
                ->withoutPreview()
                ->send();
        }

        $this->setLog($webhook);
    }

    private function setChat(array $request): void
    {
        $bot = TelegraphBot::where('name', env('TG_NAME_MOZGVA'))->first();
        $this->chat = TelegraphChat::where('chat_id', $request['message']['chat']['id'])
            ->where('telegraph_bot_id', $bot->id)
            ->first();

        if (! $this->chat) {
            $this->chat = $bot->chats()->create([
                'chat_id' => $request['message']['chat']['id'],
                'name' => $request['message']['chat']['username'] ?? $request['message']['chat']['first_name'],
            ]);
        }
    }

    private function setLog(array $request): void
    {
        BotUsageLog::create([
            'bot' => env('TG_NAME_MOZGVA'),
            'user_id' => $request['message']['chat']['id'],
            'username' =>  $request['message']['chat']['username'] ?? $request['message']['chat']['first_name'],
            'command' => $request['message']['text'],
        ]);
    }
}
