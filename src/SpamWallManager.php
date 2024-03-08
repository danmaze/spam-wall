<?php

namespace SpamWall;

use SpamWall\Admin\Settings;

class SpamWallManager
{
    /**
     * Run the plugin initialization and hook into WordPress.
     */
    public function run()
    {
        $this->defineAdminHooks();
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function defineAdminHooks()
    {
        $settings = new Settings();
        add_action('plugins_loaded', [$settings, 'init']);
    }
}
