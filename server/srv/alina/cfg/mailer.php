<?php

return [
    'admin' => [
        'Host'       => getenv('MAILER_HOST'),
        'Port'       => getenv('MAILER_PORT'),
        'Username'   => getenv('MAILER_USER'),
        'Password'   => getenv('MAILER_PASS'),
        'FromName'   => getenv('MAILER_FROM'),
        'SMTPSecure' => getenv('MAILER_SECU'),
    ],
];
