<?php

return [
    /*
	|--------------------------------------------------------------------------
	| Notificare REST API
	|--------------------------------------------------------------------------
	*/


    // Used for signing in to your account and retrieving a session token
    'email' => null,
    'password' => null,
    // Used for all calls that need account-wide access, such as stats, lists of applications, etc.
    'token' => null,

    //
    'applicationKey' => 'YOUR-APP-KEY-HERE',

    // Only used for calls that originate from the device, e.g., registration, tags.
    'applicationSecret' => null,

    // Used for calls that are related to a specific application and originate from your backend system, e.g., push, user segments, user lists, device lists, regions.
    'masterSecret' => 'YOUR-REST-API-KEY-HERE',
];