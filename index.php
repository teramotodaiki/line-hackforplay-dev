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
// Webhook event objects
$events_json = filter_input(INPUT_POST, 'events');
$events = json_decode($events_json);
if (!$events) {
    $log->error('POST:events is not found');
    exit;
}
// bot client
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);

foreach ($events as $event) {
    $log->debug("$event->timestamp $event->type");
}
