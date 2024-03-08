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
     * Initializes the comment classifier.
     */
    public function init()
    {
        add_filter('pre_comment_approved', [$this, 'classifyComment'], 10, 1);
    }

    /**
     * Classifies a comment as spam or ham.
     * 
     * @param array $commentData Comment data.
     * @return array Comment data, potentially modified to mark as spam.
     */
    public function classifyComment($commentData)
    {
        $openAI = new OpenAI();
        $classification = $openAI->classifyComment($commentData['comment_content'], [
            'author' => $commentData['comment_author'],
            'email' => $commentData['comment_author_email'],
            'url' => $commentData['comment_author_url']
        ]);

        if ($classification === 'spam') {
            $commentData['comment_approved'] = 'spam';
        }

        return $commentData;
    }
}
