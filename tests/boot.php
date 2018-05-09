<?php

require_once 'vendor/autoload.php';

// Define version
$package = json_decode(file_get_contents('composer.json'));
define_safe('VERSION', $package->version);

// Insure a manifest directory
if (!file_exists(MANIFEST)) {
    mkdir(MANIFEST);
}

// Insure a config file
if (!file_exists(CONFIG)) {
    copy(INSTALL . '/includes/config_default.php', CONFIG);
    touch('SHOULD_DELETE_CONFIG_FILE');
}
