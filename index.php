<?php
require __DIR__ . '/vendor/autoload.php';

// .env

$dotenv = new \Dotenv\Dotenv(__DIR__);
$dotenv->load();

// Mongolog

$log = new \Monolog\Logger('Webhook');
$handler = new \Monolog\Handler\StreamHandler('./app.log', \Monolog\Logger::DEBUG);
$log->pushHandler($handler);

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
        $log->debug("{$event->timestamp} {$event->type} {$event->message->type}");
        if ($event->message->type === 'text') {
            // Reply message
            $log->debug("{$event->message->id} {$event->message->text}");
            // $reply = "すごーい！きみは{$event->message->text}のフレンズなんだね！";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('hello');
            $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
            $log->info("{$response->getHTTPStatus()} {$response->getRawBody()}");
        }
    }
}
