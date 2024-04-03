<?php

declare(strict_types=1);

namespace SpamWall\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use SpamWall\API\OpenAI;
use SpamWall\Utils\EncryptionHelper;

/**
 * Class OpenAITest
 * 
 * Tests the OpenAI API handler class for the SpamWall plugin.
 * 
 * @package SpamWall
 */
class OpenAITest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_option')->justReturn('encrypted_test_api_key');
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test the constructor of the OpenAI class to ensure 
     * it properly initializes with a decrypted API key.
     */
    public function testConstructor()
    {
        $apiKey = 'test_api_key';

        // Mocking EncryptionHelper to decrypt the API key.
        $encryptionHelper = $this->createMock(EncryptionHelper::class);
        $encryptionHelper->expects($this->once())
            ->method('decrypt')
            ->with('encrypted_test_api_key') // Use the value that get_option is mocked to return.
            ->willReturn($apiKey);

        $openAI = new OpenAI($encryptionHelper);

        $this->assertInstanceOf(OpenAI::class, $openAI);
    }

    /**
     * Test the classifyComment method with a spam comment 
     * to ensure it correctly identifies spam.
     */
    public function testClassifyCommentSpam()
    {
        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andReturn(json_encode([
                'choices' => [
                    ['message' => ['content' => 'spam']]
                ]
            ]));

        $commentContent = 'This is a spam comment.';
        $commentMetadata = [
            'author' => 'Spammer',
            'email' => 'spammer@example.com',
            'url' => 'http://spammer.com'
        ];

        $openAI = $this->getMockBuilder(OpenAI::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['makeApiRequest', 'interpretResponse'])
            ->getMock();

        $openAI->expects($this->once())
            ->method('makeApiRequest')
            ->willReturn(['body' => '{"choices":[{"message":{"content":"spam"}}]}']);

        $openAI->expects($this->once())
            ->method('interpretResponse')
            ->with(['choices' => [['message' => ['content' => 'spam']]]])
            ->willReturn('spam');

        /** @var OpenAI|\PHPUnit\Framework\MockObject\MockObject $openAI */
        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertEquals('spam', $result);
    }

    /**
     * Test the classifyComment method with a non-spam (ham) comment 
     * to ensure it correctly identifies legitimate comments.
     */
    public function testClassifyCommentHam()
    {
        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andReturn(json_encode([
                'choices' => [
                    ['message' => ['content' => 'ham']]
                ]
            ]));

        $commentContent = 'This is a genuine comment.';
        $commentMetadata = [
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'url' => 'https://example.com',
        ];

        $openAI = $this->getMockBuilder(OpenAI::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['makeApiRequest', 'interpretResponse'])
            ->getMock();

        $openAI->expects($this->once())
            ->method('makeApiRequest')
            ->willReturn(['body' => '{"choices":[{"message":{"content":"ham"}}]}']);

        $openAI->expects($this->once())
            ->method('interpretResponse')
            ->with(['choices' => [['message' => ['content' => 'ham']]]])
            ->willReturn('ham');

        /** @var OpenAI|\PHPUnit\Framework\MockObject\MockObject $openAI */
        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertEquals('ham', $result);
    }

    /**
     * Test the classifyComment method with an unclear response 
     * to ensure it handles ambiguous comments appropriately.
     */
    public function testClassifyCommentUnclear()
    {
        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andReturn(json_encode([
                'choices' => [
                    ['message' => ['content' => 'unclear']]
                ]
            ]));

        $commentContent = 'This is an ambiguous comment.';
        $commentMetadata = [
            'author' => 'Jane Smith',
            'email' => 'jane@example.com',
            'url' => 'https://example.com',
        ];

        $openAI = $this->getMockBuilder(OpenAI::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['makeApiRequest', 'interpretResponse'])
            ->getMock();

        $openAI->expects($this->once())
            ->method('makeApiRequest')
            ->willReturn(['body' => '{"choices":[{"message":{"content":"unclear"}}]}']);

        $openAI->expects($this->once())
            ->method('interpretResponse')
            ->with(['choices' => [['message' => ['content' => 'unclear']]]])
            ->willReturn(null);

        /** @var OpenAI|\PHPUnit\Framework\MockObject\MockObject $openAI */
        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertNull($result);
    }
}
