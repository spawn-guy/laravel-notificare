<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__ . "/../");
$dotenv->load();

$client = new Notificare\Notificare\NotificareClient(
    [
        /*
        |--------------------------------------------------------------------------
        | Notificare REST API
        |--------------------------------------------------------------------------
        */
        // Used for signing in to your account and retrieving a session token
        'email' => getenv('NOTIFICARE_EMAIL'),
        'password' => getenv('NOTIFICARE_PASSWORD'),
        // Used for all calls that need account-wide access, such as stats, lists of applications, etc.
        'token' => getenv('NOTIFICARE_TOKEN'),

        // Used with *Secret parameters
        'applicationKey' => getenv('NOTIFICARE_APP_KEY'),

        // Only used for calls that originate from the device, e.g., registration, tags.
        'applicationSecret' => getenv('NOTIFICARE_APP_SECRET'),

        // Used for calls that are related to a specific application and originate from your backend system, e.g., push, user segments, user lists, device lists, regions.
        'masterSecret' => getenv('NOTIFICARE_MASTER_SECRET'),
    ]
);

echo $client->testCredentials();


$notification = [
    "title" => "This is a title",
    "subtitle" => "This is a subtitle",
    "message" => "This is a message",
    "type" => "re.notifica.notification.Alert",
    "sound" => "default",
];
$criteria = [
    "tagsCriteria" => [
        [
            "quantifier" => "all",
            "tags" => [
                "territory=3",
                "region=2",
                "store=1",
            ]
        ],
        /*[
            "quantifier" => "all",
            "tags" => [
                "territory=5",
                "region=6",
                "store=6",
            ]
        ],*/
        [
            "quantifier" => "all",
            "tags" => [
                "territory=all",
                "region=all",
                "store=all",
            ],
        ],
        /*[
            "quantifier" => "all",
            "tags" => [
                //"ein=4445",//lucas
                "ein=000000000",//freek
            ]
        ],*/
        /*[
            "quantifier" => "none",
            "tags" => ["test3", "test4"]
        ],*/
    ]
];

$client->sendNotificationToCriteria($notification, $criteria);