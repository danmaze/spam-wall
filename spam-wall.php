<?php

/**
 * Plugin Name:       Spam Wall
 * Plugin URI:        https://github.com/danmaze/spam-wall
 * Description:       Protects your WordPress site from spam using OpenAI's GPT models to intelligently classify comments as spam or ham.
 * Version:           1.0.0
 * Requires at least: 5.3
 * Requires PHP:      7.2
 * Author:            Daniel Ihenetu
 * Author URI:        https://github.com/danmaze
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain:       spam-wall
 */

if (!defined('WPINC')) {
    die;
}

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use SpamWall\SpamWallManager;
use SpamWall\Admin\Settings;
use SpamWall\Comment\Classifier;
use SpamWall\API\OpenAI;
use SpamWall\Utils\EncryptionHelper;

$settings = new Settings();
$encryptionHelper = new EncryptionHelper();
$openAI = new OpenAI($encryptionHelper);
$classifier = new Classifier($openAI);
$spamWallManager = new SpamWallManager($settings, $classifier);
$spamWallManager->run();
