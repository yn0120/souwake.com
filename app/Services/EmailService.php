<?php

namespace App\Services;

use App\Models\MailTrackModel;
use Aws\Sqs\SqsClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public static function queueEmail($params)
    {
        try {
            $sqsClient = new SqsClient([
                'version' => 'latest',
                'region' => config('app.aws_default_region'),
            ]);

            // トラッキングトークンの生成
            $trackingToken = MailTrackModel::makeUniqueToken();

            // トラッキング用画像のURLを生成
            $scheme = request()->getScheme() ?? 'https';
            $env = config('app.env_domain');
            $domain = config('app.domain');
            $trackingPixel = "{$scheme}://{$env}ch.{$domain}/api/mail_tracks/{$trackingToken}";

            // メール本文にトラッキングピクセルを追加
            $bodyWithTracking = "<img src='{$trackingPixel}' width='1' height='1' />{$params['body']}";

            $info = [
                'from' => $params['from'],
                'to' => $params['to'],
                'subject' => $params['subject'],
                'body' => $bodyWithTracking,
                'type' => 'email',
                'timestamp' => Carbon::now()->toIso8601String(),
            ];
            if (isset($params['cc']) && $params['cc']) {
                $info['cc'] = $params['cc'];
            }
            if (isset($params['bcc']) && $params['bcc']) {
                $info['bcc'] = $params['bcc'];
            }
            if (isset($params['attachments']) && $params['attachments']) {
                foreach ($params['attachments'] as $attachment) {
                    $info['attachments'][] = [
                        'filename' => $attachment->getClientOriginalName(),
                        'content' => base64_encode(file_get_contents($attachment->getRealPath())),
                        'contentType' => $attachment->getMimeType(),
                    ];
                }
            }

            $messageBody = json_encode($info);

            $result = $sqsClient->sendMessage([
                'QueueUrl' => config('app.aws_queue_url'),
                'MessageBody' => $messageBody,
            ]);

            // トラッキング情報をDBに保存
            DB::table('mail_tracks')->insert([
                'message_id' => $result->get('MessageId'),
                'email' => $info['to'],
                'token' => $trackingToken,
                'sent_at' => Carbon::now(),
            ]);

            // ロギング
            Log::info('Email queued successfully', [
                'messageId' => $result->get('MessageId'),
                'to' => $info['to'],
            ]);

            return $result->get('MessageId');
        } catch (\Throwable $e) {
            Log::error("Failed to queue email {$e->getMessage()}");

            throw $e;
        }
    }
}
