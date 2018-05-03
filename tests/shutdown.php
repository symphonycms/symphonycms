<?php

require_once 'vendor/autoload.php';

// Delete default config file
if (file_exists('SHOULD_DELETE_CONFIG_FILE')) {
    unlink(CONFIG);
    unlink('SHOULD_DELETE_CONFIG_FILE');
    echo 'Default config file deleted.' . PHP_EOL;
}
