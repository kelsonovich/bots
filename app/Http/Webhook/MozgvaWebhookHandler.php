<?php

namespace App\Http\Webhook;

use App\Models\BotUsageLog;
use App\Service\Response\Mozgva;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Http\Request;

class MozgvaWebhookHandler extends WebhookHandler
{
    protected TelegraphChat $chat;
    private bool $isAdmin;

    public function webhook(Request $request)
    {
        $webhook = $request->json()->all();

        if (isset($webhook['callback_query'])) {

            $this->setChat($webhook['callback_query']['message']);

            $this->callback($webhook['callback_query']);

            $this->isAdmin = Mozgva::isAdmin($webhook['callback_query']['message']);

            $this->setLog($webhook['callback_query']);

        } else if (isset($webhook['message'])) {

            $this->setChat($webhook['message']);

            $this->message($webhook['message']);

            $this->isAdmin = Mozgva::isAdmin($webhook['message']);

            $this->setLog($webhook);

        }
    }

    /** Обработка callback'ов */
    public function callback(array $webhook): void
    {
        $callback = explode(';', $webhook['data']);
        $action = explode(':', $callback[0])[1];
        $params = explode(':', $callback[1])[1];

        if ($action === 'schedule') {
            [$response, $keyboard] = Mozgva::schedule((int) $params);

            $response = Mozgva::prepare($response);

            $this->chat->edit($webhook['message']['message_id'])->html($response[0])->send();
        } else if ($action === 'teamList') {
            [$response, $keyboard] = Mozgva::teamList((int) $params);
        }

        $this->chat->replaceKeyboard(
            messageId: $webhook['message']['message_id'],
            newKeyboard: $keyboard ?? []
        )->send();
    }

    /** Обработка обычных команд */
    public function message(array $message): void
    {
        [$response, $inlineButtons] = Mozgva::execute($message['text'], $this->isAdmin);

        $originalButtons = Mozgva::BUTTONS;

        if ($this->isAdmin) {
            $originalButtons = array_merge($originalButtons, Mozgva::ADMIN_BUTTONS);
        }

        $keyboard = ReplyKeyboard::make();
        foreach ($originalButtons as $row) {
            $buttons = [];
            foreach ($row as $button) {
                $buttons[] = ReplyButton::make($button);
            }

            $keyboard = $keyboard->row($buttons);
        }

        if ($this->isAdmin) {
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

    private function setChat(array $from): void
    {
        $bot = TelegraphBot::where('name', env('TG_NAME_MOZGVA'))->first();

        $this->chat = TelegraphChat::where([
            'chat_id' => $from['chat']['id'],
            'telegraph_bot_id' => $bot->id,
        ])->first();

        if (!$this->chat) {
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
            'username' => $request['message']['chat']['username'] ?? $request['message']['chat']['first_name'],
            'command' => $request['message']['text'],
        ]);
    }
}
