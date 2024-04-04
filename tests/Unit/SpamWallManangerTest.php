<?php

declare(strict_types=1);

namespace SpamWall\Tests\Unit;

use Brain\Monkey\Actions;
use Mockery;
use SpamWall\Admin\Settings;
use SpamWall\Comment\Classifier;
use SpamWall\SpamWallManager;

/**
 * Class SpamWallManagerTest
 *
 * Tests the SpamWallManager class for the SpamWall plugin.
 *
 * @package SpamWall
 */
class SpamWallManagerTest extends AbstractUnitTestCase
{
    /**
     * Test the constructor of the SpamWallManager class to ensure
     * it properly initializes the Settings and Classifier instances.
     */
    public function testConstructor()
    {
        $settings = Mockery::mock(Settings::class);
        $classifier = Mockery::mock(Classifier::class);

        $manager = new SpamWallManager($settings, $classifier);

        $this->assertInstanceOf(SpamWallManager::class, $manager);
    }

    /**
     * Test the run method to verify that it registers the necessary hooks.
     */
    public function testRun()
    {
        $settings = Mockery::mock(Settings::class);
        $classifier = Mockery::mock(Classifier::class);

        $manager = new SpamWallManager($settings, $classifier);

        Actions\expectAdded('plugins_loaded')->once()->with([$settings, 'init']);

        $classifier->shouldReceive('init')->once();

        $manager->run();
    }
}
