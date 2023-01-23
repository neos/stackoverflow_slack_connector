<?php
declare(strict_types=1);

namespace StackoverflowSlackConnectorTests\EndToEnd;

use StackoverflowSlackConnector\Connector;
use StackoverflowSlackConnectorTests\AbstractTestCase;

final class ConnectorTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        $this->createTestDirectory();
    }

    protected function tearDown(): void
    {
        $this->removeTestDirectory();
        $this->clearEnvVars();
    }

    public function testEndToEnd(): void
    {
        $slackWebhookUrl = getenv('SLACK_WEBHOOK_URL');
        $stackAppsKey = getenv('STACK_APPS_KEY');

        $this->assertNotEmpty(
            $slackWebhookUrl,
            <<<'NOWDOC'
The SLACK_WEBHOOK_URL environment variable is required to test the connector end-to-end. For example 
----------
SLACK_WEBHOOK_URL="https://hooks.slack.com/services/segment1/segment2/segment3" ./vendor/bin/phpunit
----------
Visit https://api.slack.com/messaging/webhooks to learn how to set up a webhook for your own Slack workspace.
NOWDOC);

        $expected = [
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

        $this->addFileToTestDirectory('webhooks.ini', <<<HEREDOC
[neoscms]
    neoscms = $slackWebhookUrl
HEREDOC);
        $this->setEnvVar('hooksfile', $this->getPathOfTestDirectoryFile('webhooks.ini'));
        $this->setEnvVar('lastexecutionfile', $this->getPathOfTestDirectoryFile('last_execution.txt'));
        if (!empty($stackAppsKey)) {
            $this->addFileToTestDirectory('key.txt', $stackAppsKey);
            $this->setEnvVar('keyfile', $this->getPathOfTestDirectoryFile('key.txt'));
        }

        $connector = new Connector();
        $connector->loadStackAppsKeyIfAvailable();
        $connector->loadSlackWebhookUrls();
        $mainTags = $connector->getMainTags();
        foreach ($mainTags as $mainTag) {
            $questions = $connector->fetchLatestQuestionsFromStackOverflow(
                $mainTag,
                strtotime('19 January 2023'),
                strtotime('20 January 2023')
            );
            $messages = $connector->convertQuestionsToSlackMessages($questions, $mainTag);
            $connector->sendMessagesToSlack($messages, $mainTag);
        }
        if (!empty($messages)) {
            $connector->updateLastExecution();
        }

        $this->assertEquals($expected, $messages);
        $this->assertFileExists($this->getPathOfTestDirectoryFile('last_execution.txt'));
        // You should have received the StackOverflow question
        // "Neos CMS 7: Newly created node disappears in the document tree until cache cleared"
        // in your Slack channel.
    }
}
