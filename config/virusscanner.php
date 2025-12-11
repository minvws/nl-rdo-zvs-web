<?php

declare(strict_types=1);

return [
    /*
     * Options: "clamav", "fake"
     */
    'default' => env('VIRUSSCANNER_DEFAULT', 'clamav'),

    'clamav' => [
        'socket' => env('VIRUSSCANNER_SOCKET', 'unix:///var/run/clamav/clamd.ctl'),
        'socket_read_timeout' => env('CLAMAV_SOCKET_READ_TIMEOUT', 30),
    ],
];
