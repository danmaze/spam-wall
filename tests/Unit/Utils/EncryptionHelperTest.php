<?php

declare(strict_types=1);

namespace SpamWall\Tests\Unit;

use Brain\Monkey\Functions;
use SpamWall\Utils\EncryptionHelper;

/**
 * Class EncryptionHelperTest
 *
 * Tests the EncryptionHelper class for the SpamWall plugin.
 *
 * @package SpamWall
 */
class EncryptionHelperTest extends AbstractUnitTestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('SPAM_WALL_ENCRYPTION_KEY')) {
            define('SPAM_WALL_ENCRYPTION_KEY', 'test_encryption_key');
        }
    }

    /**
     * Test encryption and decryption with valid input.
     *
     * @dataProvider validInputDataProvider
     */
    public function testEncryptionAndDecryptionWithValidInput($value)
    {
        $encryptionHelper = new EncryptionHelper();

        $encrypted = $encryptionHelper->encrypt($value);
        $this->assertNotEquals($value, $encrypted);

        $decrypted = $encryptionHelper->decrypt($encrypted);
        $this->assertEquals($value, $decrypted);
    }

    /**
     * Data provider for valid input.
     *
     * @return array
     */
    public function validInputDataProvider()
    {
        return [
            [''],
            ['This is a test string'],
            ['Special characters: !@#$%^&*()'],
            [str_repeat('a', 1000)],
        ];
    }

    /**
     * Test encryption and decryption without openssl extension by mocking it.
     */
    public function testEncryptionAndDecryptionWithoutOpenSSLExtension()
    {
        // Mocking extension_loaded to return false for openssl
        Functions\when('extension_loaded')->justReturn(false);

        $encryptionHelper = new EncryptionHelper();

        $value = 'Test value';
        $encrypted = $encryptionHelper->encrypt($value);

        // The value should not change since openssl is mocked as not loaded
        $this->assertEquals($value, $encrypted);

        $decrypted = $encryptionHelper->decrypt($encrypted);
        $this->assertEquals($value, $decrypted);
    }


    /**
     * Test encryption and decryption with valid input and encryption key defined.
     *
     * @dataProvider validInputDataProvider
     */
    public function testEncryptionAndDecryptionWithEncryptionKey($value)
    {
        $encryptionHelper = new EncryptionHelper();

        $encrypted = $encryptionHelper->encrypt($value);
        $this->assertNotEquals($value, $encrypted);

        $decrypted = $encryptionHelper->decrypt($encrypted);
        $this->assertEquals($value, $decrypted);
    }

    /**
     * Test encryption and decryption with valid input and encryption key not defined.
     *
     * @dataProvider validInputDataProvider
     */
    public function testEncryptionAndDecryptionWithoutEncryptionKey($value)
    {
        // Mocking defined to return false for SPAM_WALL_ENCRYPTION_KEY
        Functions\when('defined')->justReturn(false);

        $encryptionHelper = new EncryptionHelper();

        $encrypted = $encryptionHelper->encrypt($value);
        $this->assertEquals($value, $encrypted);

        $decrypted = $encryptionHelper->decrypt($encrypted);
        $this->assertEquals($value, $decrypted);
    }

    /**
     * Test decryption with invalid base64 input.
     *
     * @dataProvider invalidBase64InputDataProvider
     */
    public function testDecryptionWithInvalidBase64Input($invalidInput)
    {
        $encryptionHelper = new EncryptionHelper();

        $decrypted = $encryptionHelper->decrypt($invalidInput);
        $this->assertEquals($invalidInput, $decrypted);
    }

    /**
     * Data provider for invalid base64 input.
     *
     * @return array
     */
    public function invalidBase64InputDataProvider()
    {
        return [
            ['invalid base64'],
            ['!@#$%^&*()'],
            ['This is not a valid base64 string'],
        ];
    }
}
