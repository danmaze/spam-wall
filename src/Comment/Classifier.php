<?php

/**
 * Handles comment classification using OpenAI.
 * 
 * @package SpamWall
 */

namespace SpamWall\Comment;

use SpamWall\API\OpenAI;

class Classifier
{
    /**
     * The OpenAI instance.
     *
     * @var OpenAI
     */
    private $openAI;

    /**
     * Classifier constructor.
     *
     * @param OpenAI $openAI The OpenAI instance.
     */
    public function __construct(OpenAI $openAI)
    {
        $this->openAI = $openAI;
    }

    /**
     * Initializes the comment classifier.
     */
    public function init()
    {
        add_filter('pre_comment_approved', [$this, 'classifyComment'], 10, 2);
    }

    /**
     * Classifies a comment as spam or ham.
     * 
     * @param string|int $approved The approval status.
     * @param array $commentData Comment data.
     * @return string|int Possibly modified approval status.
     */
    public function classifyComment($approved, $commentData)
    {
        $classification = $this->openAI->classifyComment($commentData['comment_content'], [
            'author' => $commentData['comment_author'],
            'email' => $commentData['comment_author_email'],
            'url' => $commentData['comment_author_url']
        ]);

        if ($classification === 'spam') {
            $approved = 'spam';
        }

        return $approved;
    }
}