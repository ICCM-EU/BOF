<?php

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'secrettoken' => 'topsecret', // needed for authentication with JWT
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
