<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

/**
 * office.souwake.com/secrets の固定パスワード認証に失敗した際にSlackへ通知する。
 * Incoming Webhook URL宛にルーティングする想定（`Notification::route('slack', $webhookUrl)`）。
 */
class SecretsPasswordFailedNotification extends Notification
{
    public function __construct(
        private readonly string $ip,
        private readonly ?string $userAgent,
    ) {
    }

    /**
     * @return array<string>
     */
    public function via($notifiable): array
    {
        return ['slack'];
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->error()
            ->content('🚨 ファイル機能(/secrets)でパスワード認証に失敗しました。')
            ->attachment(function ($attachment) {
                $attachment->fields([
                    'IPアドレス' => $this->ip,
                    'User-Agent' => $this->userAgent ?: '(不明)',
                    '日時' => now()->toDateTimeString(),
                ]);
            });
    }
}
