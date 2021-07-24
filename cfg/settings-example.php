<?php

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'secrettoken' => 'topsecret', // needed for authentication with JWT
        'PrepBofId' => 1, // BOF for the prep team
        'fallback_language' => 'en',
        'website_type' => 'bof',
        'workshop_icon' => 'noun_workshop_2457878',
        // DB Settings
        'db' => [
            'host' => 'localhost',
            'name' => 'mydbname',
            'user' => 'myuser',
            'pass' => 'mypwd'
        ]
    ],
];

?>
