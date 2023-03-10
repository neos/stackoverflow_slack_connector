<?php
declare(strict_types=1);

namespace StackoverflowSlackConnectorTests;

use StackoverflowSlackConnector\Connector;

final class ConnectorTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        $this->createTestDirectory();
    }

    protected function tearDown(): void
    {
        $this->removeTestDirectory();
    }

    public function convertQuestionsToSlackMessagesDataProvider(): array
    {
        return [
            'block Slack message' => [
                Connector::SLACK_MESSAGE_STRUCTURE_BLOCK,
                $this->getConvertQuestionsToBlockSlackMessagesExpectation()
            ],
            'legacy Slack message' => [
                Connector::SLACK_MESSAGE_STRUCTURE_LEGACY,
                $this->getConvertQuestionsToLegacySlackMessagesExpectation()
            ]
        ];
    }

    /**
     * @dataProvider convertQuestionsToSlackMessagesDataProvider
     * @group unit
     */
    public function testConvertQuestionsToSlackMessages(string $slackMessageStructure, array $expected): void
    {
        $connector = new Connector();
        $connector->setSlackWebhookUrls([
            'neoscms' => [
                'neoscms' => 'https://hooks.slack.com/services/segment1/segment2/segment3'
            ]
        ]);
        $connector->setSlackMessageStructure($slackMessageStructure);
        $questions = $this->getFetchQuestionsFromStackOverflowFixture();
        $actual = $connector->convertQuestionsToSlackMessages($questions, 'neoscms');
        $this->assertEquals($expected, $actual);
    }

    /**
     * Fetched with https://api.stackexchange.com/2.3/questions?site=stackoverflow&filter=withbody&order=asc&tagged=neoscms&fromdate=1674086400&todate=1674172800.
     */
    private function getFetchQuestionsFromStackOverflowFixture(): array
    {
        return json_decode(<<<'NOWDOC'
{
  "items": [
    {
      "tags": [
        "neoscms"
      ],
      "owner": {
        "account_id": 11777320,
        "reputation": 53,
        "user_id": 8618257,
        "user_type": "registered",
        "profile_image": "https://i.stack.imgur.com/K0MuC.jpg?s=256&g=1",
        "display_name": "Alexander Nitsche",
        "link": "https://stackoverflow.com/users/8618257/alexander-nitsche"
      },
      "is_answered": false,
      "view_count": 35,
      "answer_count": 0,
      "score": 0,
      "last_activity_date": 1674220356,
      "creation_date": 1674129996,
      "last_edit_date": 1674220356,
      "question_id": 75171996,
      "link": "https://stackoverflow.com/questions/75171996/neos-cms-7-newly-created-node-disappears-in-the-document-tree-until-cache-clear",
      "title": "Neos CMS 7: Newly created node disappears in the document tree until cache cleared",
      "body": "<p>Is the document tree cached in the Neos CMS backend? I have created two pages under &quot;Home&quot;: &quot;Neos CMS&quot; and &quot;Blog&quot; and they are displayed correctly in the main menu of the page preview, but when I click on one of the two page nodes in the document tree, the &quot;Blog&quot; node disappears in the document tree. Only when I click on the &quot;Home&quot; node or the tree refresh button, the &quot;Blog&quot; node temporarily reappears. When flushing the cache via CLI command <code>./flow flow:cache:flush</code> the node becomes permanently visible.</p>\n<p>This behavior is browser independent; normally I use Firefox, just now I used Vivaldi with default settings.</p>\n<p>I used the <a href=\"https://github.com/code-q-web-factory/Neos-Skeleton\" rel=\"nofollow noreferrer\">CodeQ Skeleton</a> as the base distribution, but others have confirmed the behavior for the official <a href=\"https://github.com/neos/neos-base-distribution\" rel=\"nofollow noreferrer\">Neos Base Distribution</a> as well.</p>\n<p>I can provide a bash script that builds up a Neos instance for repeating reproduction of this behaviour.</p>\n"
    }
  ],
  "has_more": false,
  "quota_max": 10000,
  "quota_remaining": 9940
}
NOWDOC, true);
    }

    private function getConvertQuestionsToBlockSlackMessagesExpectation(): array
    {
        return [
            'neoscms' => [
                75171996 => [
                    'attachments' => [
                        [
                            'fallback' => 'New question in StackOverflow: Neos CMS 7: Newly created node disappears in the document tree until cache cleared',
                            'color' => '#F2740D',
                            'blocks' => [
                                [
                                    'type' => 'section',
                                    'text' => [
                                        'type' => 'mrkdwn',
                                        'text' => '*<https://stackoverflow.com/questions/75171996/neos-cms-7-newly-created-node-disappears-in-the-document-tree-until-cache-clear|Neos CMS 7: Newly created node disappears in the document tree until cache cleared>*',
                                    ],
                                ],
                                [
                                    'type' => 'section',
                                    'text' => [
                                        'type' => 'mrkdwn',
                                        'text' => 'Is the document tree cached in the Neos CMS backend? I have created two pages under "Home": "Neos CMS" and "Blog" and they are displayed correctly in the main menu of the page preview, but when I click on one of the two page nodes in the document tree, the "Blog" node disappears in the document tree. Only when I click on the "Home" node or the tree refresh button, the "Blog" node temporarily reappears. When flushing the cache via CLI command `./flow flow:cache:flush` the node becomes permanently visible.

This behavior is browser independent; normally I use Firefox, just now I used Vivaldi with default settings.

I used the <https://github.com/code-q-web-factory/Neos-Skeleton|CodeQ Skeleton> as the base distribution, but others have confirmed the behavior for the official <https://github.com/neos/neos-base-distribution|Neos Base Distribution> as well.

I can provide a bash script that builds up a Neos instance for repeating reproduction of this behaviour.',
                                    ],
                                    'accessory' => [
                                        'type' => 'image',
                                        'image_url' => 'https://i.stack.imgur.com/K0MuC.jpg?s=256&g=1',
                                        'alt_text' => 'Alexander Nitsche',
                                    ],
                                ],
                                [
                                    'type' => 'context',
                                    'elements' => [
                                        [
                                            'type' => 'mrkdwn',
                                            'text' => 'Posted: <!date^1674129996^{date_pretty} at {time}|2023-01-19 12:06 UTC>',
                                        ],
                                        [
                                            'type' => 'plain_text',
                                            'text' => 'Tags: neoscms',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getConvertQuestionsToLegacySlackMessagesExpectation(): array
    {
        return [
            'neoscms' => [
                75171996 => [
                    'attachments' => [
                        [
                            'fallback' => 'New question in StackOverflow: Neos CMS 7: Newly created node disappears in the document tree until cache cleared',
                            'title' => 'Neos CMS 7: Newly created node disappears in the document tree until cache cleared',
                            'title_link' => 'https://stackoverflow.com/questions/75171996/neos-cms-7-newly-created-node-disappears-in-the-document-tree-until-cache-clear',
                            'thumb_url' => 'https://i.stack.imgur.com/K0MuC.jpg?s=256&g=1',
                            'text' => <<<'NOWDOC'
Is the document tree cached in the Neos CMS backend? I have created two pages under "Home": "Neos CMS" and "Blog" and they are displayed correctly in the main menu of the page preview, but when I click on one of the two page nodes in the document tree, the "Blog" node disappears in the document tree. Only when I click on the "Home" node or the tree refresh button, the "Blog" node temporarily reappears. When flushing the cache via CLI command `./flow flow:cache:flush` the node becomes permanently visible.

This behavior is browser independent; normally I use Firefox, just now I used Vivaldi with default settings.

I used the <https://github.com/code-q-web-factory/Neos-Skeleton|CodeQ Skeleton> as the base distribution, but others have confirmed the behavior for the official <https://github.com/neos/neos-base-distribution|Neos Base Distribution> as well.

I can provide a bash script that builds up a Neos instance for repeating reproduction of this behaviour.
NOWDOC,
                            'color' => '#F2740D',
                            'fields' => [
                                [
                                    'title' => 'Tags',
                                    'value' => 'neoscms',
                                ]
                            ],
                        ]
                    ]
                ]
            ]
        ];
    }

    public function endToEndDataProvider(): array
    {
        return [
            'default Slack message' => [
                Connector::SLACK_MESSAGE_STRUCTURE_BLOCK,
                $this->getConvertQuestionsToBlockSlackMessagesExpectation()
            ],
            'legacy Slack message' => [
                Connector::SLACK_MESSAGE_STRUCTURE_LEGACY,
                $this->getConvertQuestionsToLegacySlackMessagesExpectation()
            ]
        ];
    }

    /**
     * @dataProvider endToEndDataProvider
     * @group end-to-end
     */
    public function testEndToEnd(string $slackMessageStructure, array $expected): void
    {
        $slackWebhookUrl = getenv('SLACK_WEBHOOK_URL') ?: '';
        $filterSlackMessageStructure = getenv('SLACK_MESSAGE_STRUCTURE') ?: '';
        $stackAppsKey = getenv('STACK_APPS_KEY') ?: '';

        $this->assertNotEmpty(
            $slackWebhookUrl,
            <<<'NOWDOC'
The SLACK_WEBHOOK_URL environment variable is required to test the connector end-to-end. For example 
----------
SLACK_WEBHOOK_URL="https://hooks.slack.com/services/segment1/segment2/segment3" ./vendor/bin/phpunit
----------
Visit https://api.slack.com/messaging/webhooks to learn how to set up a webhook for your own Slack workspace.
NOWDOC);

        if ($filterSlackMessageStructure !== '' && $filterSlackMessageStructure !== $slackMessageStructure) {
            $this->markTestSkipped(sprintf('Restrict end-to-end test to %s Slack message structure.', $filterSlackMessageStructure));
        }

        $lastExecutionFilename = $this->getPathOfTestDirectoryFile('last_execution.txt');
        $this->assertFileDoesNotExist($lastExecutionFilename);

        $connector = new Connector();
        $connector->setStackAppsKey($stackAppsKey);
        $connector->setSlackWebhookUrls([
            'neoscms' => [
                'neoscms' => $slackWebhookUrl
            ]
        ]);
        $connector->setSlackMessageStructure($slackMessageStructure);
        $connector->setLastExecutionFilename($lastExecutionFilename);
        $mainTags = $connector->getMainTags();
        $fromDate = strtotime('19 January 2023');
        $toDate = strtotime('20 January 2023');
        foreach ($mainTags as $mainTag) {
            $questions = $connector->fetchQuestionsFromStackOverflow($mainTag, $fromDate, $toDate);
            $messages = $connector->convertQuestionsToSlackMessages($questions, $mainTag);
            $connector->sendMessagesToSlack($messages, $mainTag);
            $this->assertEquals($expected, $messages);
        }

        $connector->updateLastExecution();
        $this->assertFileExists($lastExecutionFilename);
        // You should have received the StackOverflow question
        // "Neos CMS 7: Newly created node disappears in the document tree until cache cleared"
        // in your Slack channel.
    }
}
