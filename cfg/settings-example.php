<?php

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'secrettoken' => 'topsecret', // needed for authentication with JWT
        'PrepBofId' => 1, // BOF for the prep team
        'fallback_language' => 'en',

        'max_length_description_preview' => 200,
        'allow_edit_nomination' => true,

        'moderated_registration' => false,
        'moderation_email' => 'somewhere@example.org',
        'moderation_token' => 'randomgenerated',

        'smtp' => [
            'host' => 'localhost',
            'port' => 465,
            'user' => 'example',
            'passwd' => 'topsecret',
            'from' => 'robot@example.org',
            'from_name' => 'ICCM XYZ'
        ],

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
