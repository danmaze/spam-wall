<?php

namespace SpamWall\API;

use Exception;
use SpamWall\Utils\OptionKey;
use SpamWall\Utils\EncryptionHelper;

/** 
 * Handles API interactions with OpenAI, specifically for classifying comments.
 * 
 * @package SpamWall
 */
class OpenAI
{

    /**
     * The OpenAI API key.
     * 
     * @var string
     */
    private $apiKey;

    /**
     * The EncryptionHelper instance.
     * 
     * @var EncryptionHelper
     */
    private $encryptionHelper;

    /**
     * The OpenAI Chat Completions API endpoint.
     * 
     * @var string
     */
    private $apiEndpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Detailed instruction for the model.
     * 
     * @var string
     */
    private $systemMessage = <<<EOT
        You are a highly efficient assistant. Your task is to assess the content and metadata of the following comment
        and determine its nature accurately. It's crucial that you analyze the given information thoroughly and provide
        a response that categorically states whether the comment is 'spam' or 'ham'. Your response should be limited to
        one and only one of these two options, 'spam' or 'ham', with absolutely no additional commentary or elaboration.
        EOT;

    /**
     * Constructor for the OpenAI API handler class.
     * Retrieves and decrypts the OpenAI API key from the database.
     * 
     * @param EncryptionHelper $encryptionHelper The EncryptionHelper instance.
     */
    public function __construct(EncryptionHelper $encryptionHelper)
    {
        $this->encryptionHelper = $encryptionHelper;
        $encrypted_api_key = get_option(OptionKey::OPENAI_API_KEY);
        $this->apiKey = $this->encryptionHelper->decrypt($encrypted_api_key);
    }

    /**
     * Classifies a comment as spam or not spam.
     * 
     * @param string $commentContent The content of the comment.
     * @param array $commentMetadata Metadata associated with the comment.
     * @return string|null 'spam', 'ham', or null on failure.
     */
    public function classifyComment($commentContent, $commentMetadata)
    {
        $model = get_option(OptionKey::MODEL_PREFERENCE, 'gpt-3.5-turbo-0125');

        $messages = [
            [
                "role" => "system",
                "content" => $this->systemMessage
            ],
            [
                "role" => "user",
                "content" => wp_json_encode(['comment' => $commentContent, 'metadata' => $commentMetadata])
            ]
        ];

        $response = $this->makeApiRequest($model, $messages);

        if (is_wp_error($response)) {
            return null;
        }

        try {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            return $this->interpretResponse($body);
        } catch (Exception $e) {
            // Log the exception for debugging purposes here if necessary
            return null;
        }
    }

    /**
     * Makes an API request to the OpenAI Chat Completions API.
     * 
     * @param string $model The model version.
     * @param array $messages The conversation messages.
     * @return array|WP_Error
     */
    protected function makeApiRequest($model, $messages)
    {
        $body = wp_json_encode([
            'model' => $model,
            'messages' => $messages,
        ]);

        $args = [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'method' => 'POST',
            'timeout' => 45,
        ];

        return wp_remote_post($this->apiEndpoint, $args);
    }

    /** 
     * Interprets the response from the OpenAI API.
     * 
     * @param array $response_body The decoded response body.
     * @return string|null 'spam', 'ham', or null if the response is not clear.
     */
    protected function interpretResponse($responseBody)
    {
        if (!empty($responseBody['choices'][0]['message']['content'])) {
            $content = strtolower(trim($responseBody['choices'][0]['message']['content']));
            // Assuming the model follows the instructions, we can expect the response to be
            // either 'spam' or 'ham' precisely.
            if ($content === 'spam') {
                return 'spam';
            } elseif ($content === 'ham') {
                return 'ham';
            }
        }
        return null; // Return null if the response doesn't match one of the expected values
    }
}
