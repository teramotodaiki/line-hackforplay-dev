<?php
require __DIR__ . '/vendor/autoload.php';

// Mongolog

$log = new \Monolog\Logger('Webhook');
$handler = new \Monolog\Handler\SlackWebhookHandler( getenv('SLACK_WEBHOOK_URL') );
$log->pushHandler($handler);
$log->alert('test');

// Webhook by LINE SDK

$httpRequestBody = file_get_contents('php://input'); // Request body string
$log->debug($httpRequestBody);
$hash = hash_hmac('sha256', $httpRequestBody, getenv('CHANNEL_SECRET'), true);
$signature = base64_encode($hash);
// Compare X-Line-Signature request header string and the signature
if ($_SERVER['HTTP_X_LINE_SIGNATURE'] !== $signature) {
    $log->error('X-Line-Signature does not match'); exit;
}
$request = json_decode($httpRequestBody);
// bot client
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);

foreach ($request->events as $event) {
    // $event == Webhook event objects https://devdocs.line.me/ja/#webhook-event-object
    if ($event->type === 'message') {
        if ($event->message->type === 'text') {
            // Reply message
            $text = strrev($event->message->text); // Reversed text of input
            $response = $bot->replyText($event->replyToken, $text);
        }
    }
}
