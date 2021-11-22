<?php

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'secrettoken' => 'topsecret', // needed for authentication with JWT
        'PrepBofId' => 1, // BOF for the prep team
        'fallback_language' => 'en',

        'max_length_description_preview' => 200,
        'allow_edit_nomination' => true,

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
