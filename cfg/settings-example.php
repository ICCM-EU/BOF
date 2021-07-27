<?php

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'secrettoken' => 'topsecret', // needed for authentication with JWT
        'PrepBofId' => 1, // BOF for the prep team
        'fallback_language' => 'en',

        'website_type' => 'bof',
        'workshop_icon' => 'noun_workshop_2457878',
        'allow_edit_nomination' => false,
        'allow_nomination_comments' => false,

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
