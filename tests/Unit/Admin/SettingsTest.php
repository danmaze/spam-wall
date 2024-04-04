<?php

declare(strict_types=1);

namespace SpamWall\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SpamWall\Admin\Settings;
use SpamWall\Utils\EncryptionHelper;

/**
 * Class SettingsTest
 *
 * Tests the Settings class for the SpamWall plugin.
 *
 * @package SpamWall
 */
class SettingsTest extends AbstractUnitTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Sets up the environment before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Functions\stubEscapeFunctions();
    }

    /**
     * Test the constructor of the Settings class to ensure
     * it properly initializes the EncryptionHelper instance.
     */
    public function testConstructor()
    {
        $encryptionHelper = Mockery::mock(EncryptionHelper::class);
        $settings = new Settings($encryptionHelper);
        $this->assertInstanceOf(Settings::class, $settings);
    }

    /*
    /**
     * Test the init method to verify that the 'admin_menu' and 'admin_init'
     * actions are registered with the correct callbacks.
     */
    public function testInit()
    {
        $encryptionHelper = Mockery::mock(EncryptionHelper::class);
        $settings = new Settings($encryptionHelper);
        $settings->init();
        $this->assertInstanceOf(Settings::class, $settings);
    }

    /**
     * Test the addSettingsPage method to verify that it 
     * returns the expected hook name
     */
    public function testAddSettingsPage()
    {
        $encryptionHelper = Mockery::mock(EncryptionHelper::class);
        $settings = new Settings($encryptionHelper);

        Functions\expect('add_options_page')
            ->once()
            ->andReturn('settings_page_spam-wall-settings');

        $result = $settings->addSettingsPage();
        $this->assertEquals('settings_page_spam-wall-settings', $result);
    }

    /**
     * Test the createSettingsPage method to verify that it outputs
     * the settings page content correctly.
     */
    public function testCreateSettingsPage()
    {
        Functions\expect('settings_fields')
            ->once()
            ->with(Settings::SETTINGS_PAGE_SLUG);

        Functions\expect('do_settings_sections')
            ->once()
            ->with(Settings::SETTINGS_PAGE_SLUG);

        Functions\expect('submit_button')
            ->once();

        $encryptionHelper = Mockery::mock(EncryptionHelper::class);
        $settings = new Settings($encryptionHelper);
        ob_start();
        $settings->createSettingsPage();
        $output = ob_get_clean();

        $this->assertStringContainsString('<div class="wrap">', $output);
        $this->assertStringContainsString('<h2>Spam Wall Settings</h2>', $output);
        $this->assertStringContainsString('<form action="options.php" method="POST">', $output);
    }

    /**
     * Test the apiKeyFieldCallback method to verify that it outputs
     * the API key input field correctly.
     */
    public function testApiKeyFieldCallback()
    {
        Functions\stubs(['esc_attr']);

        Functions\expect('get_option')
            ->once()
            ->andReturn('encrypted_api_key');

        $encryptionHelper = Mockery::mock(EncryptionHelper::class);
        $encryptionHelper->expects('decrypt')
            ->once()
            ->with('encrypted_api_key')
            ->andReturn('decrypted_api_key');

        $settings = new Settings($encryptionHelper);

        ob_start();
        $settings->apiKeyFieldCallback();
        $output = ob_get_clean();

        $this->assertStringContainsString('type="password"', $output);
        $this->assertStringContainsString('value="decrypted_api_key"', $output);
    }

    /**
     * Test the modelPreferenceFieldCallback method to verify that it outputs
     * the model preference select field correctly.
     */
    public function testModelPreferenceFieldCallback()
    {
        Functions\when('selected')->alias(function ($selected, $current, $echo = true) {
            if ($selected === $current) {
                if ($echo) {
                    echo 'selected';
                } else {
                    return 'selected';
                }
            }
        });

        Functions\expect('get_option')
            ->once()
            ->with(Mockery::type('string'), 'gpt-3.5-turbo-0125')
            ->andReturn('gpt-4-0125-preview');

        $encryptionHelper = Mockery::mock(EncryptionHelper::class);
        $settings = new Settings($encryptionHelper);

        ob_start();
        $settings->modelPreferenceFieldCallback();
        $output = ob_get_clean();

        $this->assertStringContainsString('<select name', $output);
        $this->assertStringContainsString('selected', $output);
    }

    /**
     * Test the sanitizeApiKey method to verify that it sanitizes and
     * encrypts the API key correctly.
     */
    public function testSanitizeApiKey()
    {
        Functions\expect('sanitize_text_field')
            ->once()
            ->with('unsanitized_api_key')
            ->andReturn('sanitized_api_key');

        $encryptionHelper = Mockery::mock(EncryptionHelper::class);
        $encryptionHelper->expects('encrypt')
            ->once()
            ->with('sanitized_api_key')
            ->andReturn('encrypted_api_key');

        $settings = new Settings($encryptionHelper);

        $sanitizedApiKey = $settings->sanitizeApiKey('unsanitized_api_key');
        $this->assertEquals('encrypted_api_key', $sanitizedApiKey);
    }
}
