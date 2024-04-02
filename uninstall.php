<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use SpamWall\Utils\OptionKey;

$optionKeysReflection = new ReflectionClass(OptionKey::class);

foreach ($optionKeysReflection->getConstants() as $constant => $value) {
    delete_option($value);
}
