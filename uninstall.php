<?php

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Assuming Composer's autoloader is available and can autoload the OptionKey class.
require_once __DIR__ . '/vendor/autoload.php';

use SpamWall\Utils\OptionKey;

// ReflectionClass allows accessing the class constants dynamically.
$optionKeysReflection = new ReflectionClass(OptionKey::class);

// Loop through each constant in the OptionKey class and delete the option from the database.
foreach ($optionKeysReflection->getConstants() as $constant => $value) {
    delete_option($value);  // $value contains the option name to delete.
}
