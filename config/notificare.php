<?php

return [
    /*
	|--------------------------------------------------------------------------
	| Notificare REST API
	|--------------------------------------------------------------------------
	*/
    // Used for signing in to your account and retrieving a session token
    'email' => env('NOTIFICARE_EMAIL'),
    'password' => env('NOTIFICARE_PASSWORD'),
    // Used for all calls that need account-wide access, such as stats, lists of applications, etc.
    'token' => env('NOTIFICARE_TOKEN'),

    // Used with *Secret parameters
    'applicationKey' => env('NOTIFICARE_APP_KEY'),

    // Only used for calls that originate from the device, e.g., registration, tags.
    'applicationSecret' => env('NOTIFICARE_APP_SECRET'),

    // Used for calls that are related to a specific application and originate from your backend system, e.g., push, user segments, user lists, device lists, regions.
    'masterSecret' => env('NOTIFICARE_MASTER_SECRET'),
];