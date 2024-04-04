<?php

namespace SpamWall\Tests\Unit;

use Mockery;
use SpamWall\API\OpenAI;
use SpamWall\Comment\Classifier;

class ClassifierTest extends AbstractUnitTestCase
{
    /**
     * Test the constructor of the Classifier class to ensure
     * it properly initializes the OpenAI instance.
     */
    public function testConstructor()
    {
        $openAI = Mockery::mock(OpenAI::class);
        $classifier = new Classifier($openAI);
        $this->assertInstanceOf(Classifier::class, $classifier);
    }

    /**
     * Test the init method to verify that the 'pre_comment_approved'
     * filter is registered with the correct callback.
     */
    public function testInit()
    {
        $openAI = Mockery::mock(OpenAI::class);
        $classifier = new Classifier($openAI);

        $classifier->init();

        $this->assertNotFalse(has_filter('pre_comment_approved', [$classifier, 'classifyComment']));
    }

    /**
     * Test the classifyComment method with various comments to verify
     * it sets the approved status correctly.
     * 
     * @dataProvider classifyCommentDataProvider
     */
    public function testClassifyComment($expectedApproval, $returnClassification, $commentData)
    {
        $openAI = Mockery::mock(OpenAI::class);
        $openAI->shouldReceive('classifyComment')
            ->once()
            ->andReturn($returnClassification);

        $classifier = new Classifier($openAI);

        $approved = $classifier->classifyComment('1', $commentData);
        $this->assertEquals($expectedApproval, $approved);
    }

    /**
     * Data provider for testClassifyComment.
     *
     * @return array
     */
    public function classifyCommentDataProvider()
    {
        return [
            'spam_comment' => [
                'expectedApproval' => 'spam',
                'returnClassification' => 'spam',
                'commentData' => [
                    'comment_content' => 'This is a spam comment',
                    'comment_author' => 'Spam Author',
                    'comment_author_email' => 'spam@example.com',
                    'comment_author_url' => 'https://spamexample.com',
                ],
            ],
            'ham_comment' => [
                'expectedApproval' => '1',
                'returnClassification' => 'ham',
                'commentData' => [
                    'comment_content' => 'This is a legitimate comment',
                    'comment_author' => 'Legitimate Author',
                    'comment_author_email' => 'legitimate@example.com',
                    'comment_author_url' => 'https://example.com',
                ],
            ],
        ];
    }
}
