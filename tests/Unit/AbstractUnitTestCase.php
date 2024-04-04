<?php

declare(strict_types=1);

namespace SpamWall\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class AbstractUnitTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Sets up the environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    /**
     * Tears down the environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
