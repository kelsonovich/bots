<?php

namespace App\Http\Controllers\Mozgva;

use App\Models\BotUsageLog;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Service\Response\Mozgva;

class MozgvaController extends WebhookHandler
{
    use AuthorizesRequests, ValidatesRequests;

    public function webhook(Request $request)
    {
        $webhook = $request->json()->all();
        $chat = TelegraphChat::find(15);
        try {
            if (array_key_exists('callback_query', $webhook)) {
                $this->setChat($webhook['callback_query']['message']);

//                $this->chat->replyWebhook($webhook['callback_query']['id'], '')
//                    ->withoutPreview()
//                    ->send();

                $this->reply('qqweqww');
            } else {
                $message = $webhook['message']['text'];
                $this->setChat($webhook['message']);
                $isAdmin = Mozgva::isAdmin($webhook);

                [$response, $inlineButtons] = Mozgva::execute($message, $isAdmin);

                $originalButtons = \App\Service\Response\Mozgva::BUTTONS;

                if ($isAdmin) {
                    $originalButtons = array_merge($originalButtons, \App\Service\Response\Mozgva::ADMIN_BUTTONS);
                }

                $keyboard = ReplyKeyboard::make();
                foreach ($originalButtons as $row) {
                    $buttons = [];
                    foreach ($row as $button) {
                        $buttons[] = ReplyButton::make($button);
                    }

                    $keyboard = $keyboard->row($buttons);
                }

                if ($isAdmin) {
                    $this->chat->html($response[0])
                        ->keyboard($inlineButtons)
                        ->withoutPreview()
                        ->send();
                } else {
                    foreach ($response as $responseMessage) {
                        $this->chat->html($responseMessage)
                            ->replyKeyboard($keyboard->resize())
                            ->withoutPreview()
                            ->send();
                    }
                }
            }

        } catch (\Exception $exception) {
            $chat->message('<pre>' . json_encode([$exception->getMessage()], JSON_UNESCAPED_UNICODE) . '</pre>')->send();
            $chat->message('<pre>' . json_encode($webhook, JSON_UNESCAPED_UNICODE) . '</pre>')->send();

            dd($exception, $exception->getMessage());
        }

        $this->setLog($webhook);
    }

    private function setChat(array $from): void
    {
        $bot = TelegraphBot::where('name', env('TG_NAME_MOZGVA'))->first();

        $this->chat = TelegraphChat::where([
            'chat_id' => $from['chat']['id'],
            'telegraph_bot_id' => $bot->id,
        ])->first();

        if (! $this->chat) {
            $this->chat = $bot->chats()->create([
                'chat_id' => $from['chat']['id'],
                'name' => $from['chat']['username'] ?? $from['chat']['first_name'],
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

    public function test()
    {
        return view('test');
    }
}
