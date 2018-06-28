<?php

// Boot minimal Symphony
require_once 'vendor/autoload.php';
Symphony::initialiseConfiguration([
    'region' => [
        'time_format' => 'H:i:s',
        'date_format' => 'Y/m/d',
        'datetime_separator' => ' ',
        'timezone' => 'UTC',
    ],
]);
