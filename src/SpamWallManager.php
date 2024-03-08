<?php

/** 
 * The main plugin class.
 * 
 * @package SpamWall
 */

namespace SpamWall;

use SpamWall\Admin\Settings;
use SpamWall\Comment\Classifier;

class SpamWallManager
{
    /**
     * Run the plugin initialization and hook into WordPress.
     */
    public function run()
    {
        $this->defineAdminHooks();
        $this->defineCommentHooks();
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function defineAdminHooks()
    {
        $settings = new Settings();
        add_action('plugins_loaded', [$settings, 'init']);
    }

    /**
     * Register all hooks related to comment processing.
     */
    private function defineCommentHooks()
    {
        $classifier = new Classifier();
        $classifier->init();
    }
}
