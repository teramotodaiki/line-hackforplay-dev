<?php
require __DIR__ . '/vendor/autoload.php';

// Mongolog

$log = new \Monolog\Logger('Webhook');
$handler = new \Monolog\Handler\SlackWebhookHandler( getenv('SLACK_WEBHOOK_URL') );
$log->pushHandler($handler);

// Webhook by LINE SDK
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient( getenv('CHANNEL_ACCESS_TOKEN') );
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
$signature = $_SERVER['HTTP_X_LINE_SIGNATURE']; // X-Line-Signature in header
$httpRequestBody = file_get_contents('php://input'); // Request body string

try {
    // Compare X-Line-Signature request header string and the signature
    // Success then parse request body
    $events = $bot->parseEventRequest($httpRequestBody, $signature);

    foreach ($events as $event) {
        // $event <Webhook event objects> has a message
        // See: https://devdocs.line.me/ja/#webhook-event-object
        foreach ($events as $event) {
            if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
                $log->alert('Non message event has come');
                continue;
            }
            if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
                $log->alert('Non text message has come');
                continue;
            }
            $replyText = utf8_strrev( $event->getText() ); // Reversed text of input
            $bot->replyText($event->getReplyToken(), $replyText); // Send reply!
        }
    }

} catch (Exception $e) {
    // An error was occured, see details in Slack channel!
    $log->alert("😵 {$e->getMessage()} $httpRequestBody");

}

function utf8_strrev($str){
    preg_match_all('/./us', $str, $ar);
    return join('', array_reverse($ar[0]));
}
