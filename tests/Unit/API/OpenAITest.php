<?php

declare(strict_types=1);

namespace SpamWall\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SpamWall\API\OpenAI;
use SpamWall\Utils\EncryptionHelper;
use SpamWall\Utils\OptionKey;

/**
 * Class OpenAITest
 *
 * Tests the OpenAI API handler class for the SpamWall plugin.
 *
 * @package SpamWall
 */
class OpenAITest extends AbstractUnitTestCase
{
    /**
     * Sets up the environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('get_option')
            ->alias(function ($optionName) {
                if ($optionName == OptionKey::MODEL_PREFERENCE) {
                    return 'gpt-3.5-turbo';
                }
                return 'encrypted_test_api_key';
            });
    }

    /**
     * Creates a mock EncryptionHelper instance for testing.
     */
    protected function createMockEncryptionHelper()
    {
        $encryptionHelper = Mockery::mock(EncryptionHelper::class);
        $encryptionHelper->shouldReceive('decrypt')
            ->once()
            ->with('encrypted_test_api_key')
            ->andReturn('test_api_key');

        return $encryptionHelper;
    }

    /**
     * Test the constructor of the OpenAI class to ensure
     * it properly initializes with a decrypted API key.
     */
    public function testConstructor()
    {
        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);
        $this->assertInstanceOf(OpenAI::class, $openAI);
    }

    /**
     * Data provider for testClassifyComment.
     *
     * @return array
     */
    public function classifyCommentDataProvider()
    {
        return [
            'spam' => [
                'This is a spam comment.',
                ['author' => 'Spammer', 'email' => 'spammer@example.com', 'url' => 'http://spammer.com'],
                'spam',
            ],
            'ham' => [
                'This is a genuine comment.',
                ['author' => 'John Doe', 'email' => 'john@example.com', 'url' => 'https://example.com'],
                'ham',
            ],
            'unclear' => [
                'This is an ambiguous comment.',
                ['author' => 'Jane Smith', 'email' => 'jane@example.com', 'url' => 'https://example.com'],
                null,
            ],
        ];
    }

    /**
     * Test the classifyComment method with different comment types.
     *
     * @dataProvider classifyCommentDataProvider
     */
    public function testClassifyComment($commentContent, $commentMetadata, $expectedResult)
    {
        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andReturn(json_encode([
                'choices' => [['message' => ['content' => $expectedResult ?? 'unclear']]]
            ]));

        $openAI = Mockery::mock(OpenAI::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $openAI->shouldReceive('makeApiRequest')
            ->once()
            ->andReturn(['body' => json_encode([
                'choices' => [['message' => ['content' => $expectedResult ?? 'unclear']]]
            ])]);

        $openAI->shouldReceive('interpretResponse')
            ->once()
            ->with(['choices' => [['message' => ['content' => $expectedResult ?? 'unclear']]]])
            ->andReturn($expectedResult);

        /** @var OpenAI|\Mockery\MockInterface $openAI */
        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test the makeApiRequest method to verify that it makes a POST request
     * to the correct API endpoint with the correct body and headers.
     */
    public function testMakeApiRequest()
    {
        $model = 'gpt-3.5-turbo';
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant.',
            ],
            [
                'role' => 'user',
                'content' => 'Hello!',
            ],
        ];

        $expectedBody = json_encode([
            'model' => $model,
            'messages' => $messages,
        ]);

        Functions\expect('wp_remote_post')
            ->once()
            ->with(
                'https://api.openai.com/v1/chat/completions',
                [
                    'body' => $expectedBody,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer test_api_key',
                    ],
                    'method' => 'POST',
                    'timeout' => 45,
                ]
            )
            ->andReturn(['body' => '{"choices":[{"message":{"content":"Hello! How can I assist you today?"}}]}']);

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $reflection = new \ReflectionClass($openAI);
        $method = $reflection->getMethod('makeApiRequest');
        $method->setAccessible(true);

        $response = $method->invoke($openAI, $model, $messages);

        $this->assertArrayHasKey('body', $response);
        $this->assertJson($response['body']);
    }

    /**
     * Data provider for testInterpretResponse.
     *
     * @return array
     */
    public function interpretResponseDataProvider()
    {
        return [
            'unexpected value' => [
                ['choices' => [['message' => ['content' => 'unexpected']]]],
                null,
            ],
            'empty body' => [
                [],
                null,
            ],
            'missing keys' => [
                ['choices' => [['invalid_key' => ['content' => 'test']]]],
                null,
            ],
        ];
    }

    /**
     * Test the interpretResponse method with different response bodies.
     *
     * @dataProvider interpretResponseDataProvider
     */
    public function testInterpretResponse($responseBody, $expectedResult)
    {
        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $reflection = new \ReflectionClass($openAI);
        $method = $reflection->getMethod('interpretResponse');
        $method->setAccessible(true);

        $result = $method->invoke($openAI, $responseBody);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test the classifyComment method when the API request fails
     * to ensure it returns null.
     */
    public function testClassifyCommentApiRequestFailure()
    {
        $commentContent = 'This is a comment.';
        $commentMetadata = [
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'url' => 'https://example.com',
        ];

        $wpError = Mockery::mock('WP_Error');
        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn($wpError);

        Functions\expect('is_wp_error')
            ->once()
            ->with($wpError)
            ->andReturnTrue();

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertNull($result);
    }

    /**
     * Data provider for testResponseParsingFailure.
     *
     * @return array
     */
    public function responseParsingFailureDataProvider()
    {
        return [
            'invalid json' => ['invalid json'],
            'exception' => [null, true],
        ];
    }

    /**
     * Test response parsing failure scenarios.
     *
     * @dataProvider responseParsingFailureDataProvider
     */
    public function testResponseParsingFailure($responseBody, $throwException = false)
    {
        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(['body' => $responseBody ?? '{"choices":[{"message":{"content":"ham"}}]}']);

        if ($throwException) {
            Functions\expect('wp_remote_retrieve_body')
                ->once()
                ->andThrow(new \Exception('Parsing error'));
        } else {
            Functions\expect('wp_remote_retrieve_body')
                ->once()
                ->andReturn($responseBody);
        }

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $commentContent = 'This is a comment.';
        $commentMetadata = ['author' => 'John Doe', 'email' => 'john@example.com', 'url' => 'https://example.com'];

        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertNull($result);
    }
}
