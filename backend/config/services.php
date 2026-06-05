<?php

return [
    'alert' => [
        'secret' => env('ALERT_SECRET'),
    ],

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON'),
    ],
];
