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
     * The Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * The Classifier instance.
     *
     * @var Classifier
     */
    private $classifier;

    /**
     * SpamWallManager constructor.
     *
     * @param Settings $settings The Settings instance.
     * @param Classifier $classifier The Classifier instance.
     */
    public function __construct(Settings $settings, Classifier $classifier)
    {
        $this->settings = $settings;
        $this->classifier = $classifier;
    }

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
        add_action('plugins_loaded', [$this->settings, 'init']);
    }

    /**
     * Register all hooks related to comment processing.
     */
    private function defineCommentHooks()
    {
        $this->classifier->init();
    }
}