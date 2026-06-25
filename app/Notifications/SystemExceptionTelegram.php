<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class SystemExceptionTelegram extends Notification implements ShouldQueue
{
    use Queueable;

    protected $exception;

    protected $traceId;

    public function __construct(\Throwable $exception, ?string $traceId)
    {
        $this->exception = $exception;
        $this->traceId = $traceId;
    }

    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
        // Get the first few lines of the error to avoid exceeding Telegram's character limit
        $erroMsg = substr($this->exception->getMessage(), 0, 500);
        $arquivo = $this->exception->getFile();
        $linha = $this->exception->getLine();

        return TelegramMessage::create()
            ->token(config('services.telegram-bot-api.token'))
            ->content("🚨 *EXCEÇÃO DETECTADA!* 🚨\n\n"
                .'🔍 *Trace ID:* `'.$this->traceId."`\n"
                .'🏷️ *Classe:* `'.get_class($this->exception)."`\n"
                .'📄 *Arquivo:* `'.$arquivo.':'.$linha."`\n"
                .'💬 *Erro:* '.$erroMsg
            );
    }
}
