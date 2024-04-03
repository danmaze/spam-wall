<?php

declare(strict_types=1);

namespace SpamWall\Tests\Unit;

use Brain\Monkey;
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
class OpenAITest extends AbstractUnitTestcase
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

        $openAI = Mockery::mock(OpenAI::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $openAI->shouldReceive('makeApiRequest')
            ->once()
            ->andReturn(['body' => '{"choices":[{"message":{"content":"spam"}}]}']);

        $openAI->shouldReceive('interpretResponse')
            ->once()
            ->with(['choices' => [['message' => ['content' => 'spam']]]])
            ->andReturn('spam');

        /** @var OpenAI|\Mockery\MockInterface $openAI */
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

        $openAI = Mockery::mock(OpenAI::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $openAI->shouldReceive('makeApiRequest')
            ->once()
            ->andReturn(['body' => '{"choices":[{"message":{"content":"ham"}}]}']);

        $openAI->shouldReceive('interpretResponse')
            ->once()
            ->with(['choices' => [['message' => ['content' => 'ham']]]])
            ->andReturn('ham');

        /** @var OpenAI|\Mockery\MockInterface $openAI */
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

        $openAI = Mockery::mock(OpenAI::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $openAI->shouldReceive('makeApiRequest')
            ->once()
            ->andReturn(['body' => '{"choices":[{"message":{"content":"unclear"}}]}']);

        $openAI->shouldReceive('interpretResponse')
            ->once()
            ->with(['choices' => [['message' => ['content' => 'unclear']]]])
            ->andReturn(null);

        /** @var OpenAI|\Mockery\MockInterface $openAI */
        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertNull($result);
    }

    /**
     * Test the classifyComment method with empty comment content
     * to ensure it handles the scenario appropriately.
     */
    public function testClassifyCommentEmptyContent()
    {
        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(['body' => '{"choices":[{"message":{"content":"unclear"}}]}']);

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andReturn('{"choices":[{"message":{"content":"unclear"}}]}');

        $commentContent = '';
        $commentMetadata = [
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'url' => 'https://example.com',
        ];

        $openAI = Mockery::mock(OpenAI::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $openAI->shouldReceive('interpretResponse')
            ->once()
            ->with(['choices' => [['message' => ['content' => 'unclear']]]])
            ->andReturn(null);

        /** @var OpenAI|\Mockery\MockInterface $openAI */
        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertNull($result);
    }

    /**
     * Test the classifyComment method with invalid or missing comment metadata
     * to ensure it handles the scenario appropriately.
     */
    public function testClassifyCommentInvalidMetadata()
    {
        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(['body' => '{"choices":[{"message":{"content":"unclear"}}]}']);

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andReturn('{"choices":[{"message":{"content":"unclear"}}]}');

        $commentContent = 'This is a comment with invalid metadata.';
        $commentMetadata = [
            'invalid_field' => 'Invalid value',
        ];

        $openAI = Mockery::mock(OpenAI::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $openAI->shouldReceive('interpretResponse')
            ->once()
            ->with(['choices' => [['message' => ['content' => 'unclear']]]])
            ->andReturn(null);

        /** @var OpenAI|\Mockery\MockInterface $openAI */
        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertNull($result);
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
     * Test the makeApiRequest method with an invalid API key
     * to verify that it returns a WP_Error.
     */
    public function testMakeApiRequestInvalidApiKey()
    {
        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(Mockery::mock('WP_Error')
                ->shouldReceive('get_error_code')
                ->once()
                ->andReturn('invalid_api_key')
                ->getMock());

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $reflection = new \ReflectionClass($openAI);
        $method = $reflection->getMethod('makeApiRequest');
        $method->setAccessible(true);

        $response = $method->invoke($openAI, 'gpt-3.5-turbo', []);

        $this->assertInstanceOf(Mockery\MockInterface::class, $response);
        $this->assertEquals('invalid_api_key', $response->get_error_code());
    }

    /**
     * Test the makeApiRequest method with a timeout
     * to verify that it handles the timeout gracefully.
     */
    public function testMakeApiRequestTimeout()
    {
        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(Mockery::mock('WP_Error')
                ->shouldReceive('get_error_code')
                ->once()
                ->andReturn('http_request_failed')
                ->getMock());

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $reflection = new \ReflectionClass($openAI);
        $method = $reflection->getMethod('makeApiRequest');
        $method->setAccessible(true);

        $response = $method->invoke($openAI, 'gpt-3.5-turbo', []);

        $this->assertInstanceOf(Mockery\MockInterface::class, $response);
        $this->assertEquals('http_request_failed', $response->get_error_code());
    }

    /**
     * Test the interpretResponse method with a response containing an unexpected value
     * to verify that it returns null.
     */
    public function testInterpretResponseUnexpectedValue()
    {
        $responseBody = [
            'choices' => [
                ['message' => ['content' => 'unexpected']]
            ]
        ];

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $reflection = new \ReflectionClass($openAI);
        $method = $reflection->getMethod('interpretResponse');
        $method->setAccessible(true);

        $result = $method->invoke($openAI, $responseBody);

        $this->assertNull($result);
    }

    /**
     * Test the interpretResponse method with an empty response body
     * to verify that it returns null.
     */
    public function testInterpretResponseEmptyBody()
    {
        $responseBody = [];

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $reflection = new \ReflectionClass($openAI);
        $method = $reflection->getMethod('interpretResponse');
        $method->setAccessible(true);

        $result = $method->invoke($openAI, $responseBody);

        $this->assertNull($result);
    }

    /**
     * Test the interpretResponse method with a response missing the 'choices' or 'message' keys
     * to verify the behavior.
     */
    public function testInterpretResponseMissingKeys()
    {
        $responseBody = [
            'choices' => [
                ['invalid_key' => ['content' => 'test']]
            ]
        ];

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $reflection = new \ReflectionClass($openAI);
        $method = $reflection->getMethod('interpretResponse');
        $method->setAccessible(true);

        $result = $method->invoke($openAI, $responseBody);

        $this->assertNull($result);
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

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(Mockery::mock('WP_Error')
                ->shouldReceive('get_error_code')
                ->zeroOrMoreTimes()
                ->andReturn('http_request_failed')
                ->getMock());

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertNull($result);
    }

    /**
     * Test the classifyComment method with extremely long comment content
     * to verify that it handles it correctly.
     */
    public function testClassifyCommentWithLongContent()
    {
        $longContent = str_repeat('This is a very long comment. ', 1000);
        $commentMetadata = [
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'url' => 'https://example.com',
        ];

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(['body' => '{"choices":[{"message":{"content":"ham"}}]}']);

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->with(['body' => '{"choices":[{"message":{"content":"ham"}}]}'])
            ->andReturn('{"choices":[{"message":{"content":"ham"}}]}');

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $result = $openAI->classifyComment($longContent, $commentMetadata);

        $this->assertNotNull($result);
        // Add more assertions as needed
    }

    /**
     * Test the classifyComment method with special characters or encoding
     * in the comment content and metadata.
     */
    public function testClassifyCommentWithSpecialCharacters()
    {
        $commentContent = 'This is a comment with special characters: 😊 💥 🎉';
        $commentMetadata = [
            'author' => 'Jöhn Døe',
            'email' => 'jøhn@example.com',
            'url' => 'https://exämple.com',
        ];

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(['body' => '{"choices":[{"message":{"content":"ham"}}]}']);

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->with(['body' => '{"choices":[{"message":{"content":"ham"}}]}'])
            ->andReturn('{"choices":[{"message":{"content":"ham"}}]}');

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertNotNull($result);
    }

    /**
     * Test the classifyComment method with a large number of messages in the conversation.
     */
    public function testClassifyCommentWithLargeConversation()
    {
        $commentContent = 'This is a comment in a large conversation.';
        $commentMetadata = [
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'url' => 'https://example.com',
        ];

        // Create a large number of messages
        $messages = [
            [
                "role" => "system",
                "content" => "You are a helpful assistant."
            ],
        ];

        for ($i = 0; $i < 100; $i++) {
            $messages[] = [
                "role" => "user",
                "content" => "Message $i"
            ];
        }

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(['body' => '{"choices":[{"message":{"content":"ham"}}]}']);

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->with(['body' => '{"choices":[{"message":{"content":"ham"}}]}'])
            ->andReturn('{"choices":[{"message":{"content":"ham"}}]}');

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertEquals('ham', $result);
    }

    /**
     * Test that the default model is used when no model preference is set.
     */
    public function testDefaultModelUsedWhenNoPreferenceSet()
    {
        Functions\when('get_option')
            ->alias(function ($optionName) {
                if ($optionName == OptionKey::OPENAI_API_KEY) {
                    return 'encrypted_test_api_key';
                }
                return null; // Return null when no model preference is set
            });

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturnUsing(function ($url, $args) {
                $body = json_decode($args['body'], true);
                if ($body['model'] === 'gpt-3.5-turbo-0125') {
                    return ['body' => '{"choices":[{"message":{"content":"ham"}}]}'];
                }
                return ['body' => '{"choices":[{"message":{"content":"spam"}}]}'];
            });

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andReturn('{"choices":[{"message":{"content":"ham"}}]}');

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $commentContent = 'This is a comment.';
        $commentMetadata = [
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'url' => 'https://example.com',
        ];

        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertEquals('ham', $result);
    }

    /**
     * Test with different model preferences and verify that it uses the correct model in the API request.
     */
    public function testDifferentModelPreferences()
    {
        $modelPreferences = ['gpt-3.5-turbo', 'gpt-4', 'gpt-3.5-turbo-0301'];

        foreach ($modelPreferences as $modelPreference) {
            Functions\when('get_option')
                ->alias(function ($optionName) use ($modelPreference) {
                    if ($optionName == OptionKey::MODEL_PREFERENCE) {
                        return $modelPreference;
                    }
                    return 'encrypted_test_api_key';
                });

            Functions\expect('wp_remote_post')
                ->once()
                ->with(Mockery::any(), Mockery::on(function ($args) use ($modelPreference) {
                    $body = json_decode($args['body'], true);
                    return $body['model'] === $modelPreference; // Verify the correct model is used
                }))
                ->andReturn(['body' => '{"choices":[{"message":{"content":"ham"}}]}']);

            Functions\expect('wp_remote_retrieve_body')
                ->once()
                ->andReturn('{"choices":[{"message":{"content":"ham"}}]}');

            $encryptionHelper = $this->createMockEncryptionHelper();
            /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
            $openAI = new OpenAI($encryptionHelper);

            $commentContent = 'This is a comment.';
            $commentMetadata = [
                'author' => 'John Doe',
                'email' => 'john@example.com',
                'url' => 'https://example.com',
            ];

            $result = $openAI->classifyComment($commentContent, $commentMetadata);

            $this->assertEquals('ham', $result);
        }
    }

    /**
     * Test with a modified system message and verify that it affects the API response accordingly.
     */
    public function testModifiedSystemMessage()
    {
        $modifiedSystemMessage = 'You are a spam detection assistant.';

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturnUsing(function ($url, $args) use ($modifiedSystemMessage) {
                $body = json_decode($args['body'], true);
                if ($body['messages'][0]['content'] === $modifiedSystemMessage) {
                    return ['body' => '{"choices":[{"message":{"content":"spam"}}]}'];
                }
                return ['body' => '{"choices":[{"message":{"content":"ham"}}]}'];
            });

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andReturn('{"choices":[{"message":{"content":"spam"}}]}');

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $result = $openAI->classifyComment('This is a comment.', []);

        $this->assertEquals('spam', $result);
    }

    /**
     * Test with an invalid JSON response and verify that it handles the parsing error gracefully.
     */
    public function testInvalidJsonResponse()
    {
        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(['body' => 'invalid json']);

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andReturn('invalid json');

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $commentContent = 'This is a comment.';
        $commentMetadata = [
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'url' => 'https://example.com',
        ];

        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertNull($result);
    }

    /**
     * Test with an exception during response parsing and verify that it is handled appropriately.
     */
    public function testExceptionDuringResponseParsing()
    {
        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(['body' => '{"choices":[{"message":{"content":"ham"}}]}']);

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andThrow(new \Exception('Parsing error'));

        $encryptionHelper = $this->createMockEncryptionHelper();
        /** @var EncryptionHelper|Mockery\MockInterface $encryptionHelper */
        $openAI = new OpenAI($encryptionHelper);

        $commentContent = 'This is a comment.';
        $commentMetadata = [
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'url' => 'https://example.com',
        ];

        $result = $openAI->classifyComment($commentContent, $commentMetadata);

        $this->assertNull($result);
    }
}
