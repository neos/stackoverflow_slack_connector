<?php
declare(strict_types=1);

namespace StackoverflowSlackConnector;

/**
 * This file was part of the TYPO3 CMS project. It has been forked and
 * adjusted for Neos.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

class Connector
{
    public const SLACK_MESSAGE_STRUCTURE_BLOCK = 'block';
    public const SLACK_MESSAGE_STRUCTURE_LEGACY = 'legacy';

    /**
     * @var string
     */
    protected string $stackAppsKey = '';

    /**
     * @var string
     */
    protected string $stackExchangeApiUrl = 'https://api.stackexchange.com/2.3';

    /**
     * @var array
     */
    protected array $slackWebhookUrls = [];

    /**
     * @var string
     */
    protected string $slackMessageStructure = self::SLACK_MESSAGE_STRUCTURE_BLOCK;

    /**
     * @var string
     */
    protected string $lastExecutionFilename = 'last_execution.txt';

    /**
     * @param string $stackAppsKey
     */
    public function setStackAppsKey(string $stackAppsKey): void
    {
        $this->stackAppsKey = str_replace("\n", '', $stackAppsKey);
    }

    /**
     * @param array $slackWebhookUrls
     */
    public function setSlackWebhookUrls(array $slackWebhookUrls): void
    {
        $this->slackWebhookUrls = $slackWebhookUrls;
    }

    /**
     * @param string $slackMessageStructure
     */
    public function setSlackMessageStructure(string $slackMessageStructure): void
    {
        $this->slackMessageStructure = $slackMessageStructure;
    }

    /**
     * @param string $lastExecutionFilename
     */
    public function setLastExecutionFilename(string $lastExecutionFilename): void
    {
        $this->lastExecutionFilename = $lastExecutionFilename;
    }

    /**
     * @return array
     */
    public function getMainTags(): array
    {
        return array_keys($this->slackWebhookUrls);
    }

    /**
     * @param string $tag
     * @param int $fromDate
     * @param int $toDate
     * @return array|null
     *
     * @see https://api.stackexchange.com/docs/questions
     */
    public function fetchQuestionsFromStackOverflow(string $tag, int $fromDate = -1, int $toDate = -1): ?array
    {
        $questionsUrlQuery = [
            'site' => 'stackoverflow',
            'filter' => 'withbody',
            'order' => 'asc',
            'tagged' => $tag,
        ];
        if ($fromDate > -1) {
            $questionsUrlQuery['fromdate'] = $fromDate;
        }
        if ($toDate > -1) {
            $questionsUrlQuery['todate'] = $toDate;
        }
        if (!empty($this->stackAppsKey)) {
            $questionsUrlQuery['key'] = $this->stackAppsKey;
        }
        $questionsUrl = $this->stackExchangeApiUrl . '/questions?' . http_build_query($questionsUrlQuery);

        $context = stream_context_create([
            'http' => [
                'user_agent' => 'neos-slack-connector'
            ]
        ]);

        $questions = file_get_contents('compress.zlib://' . $questionsUrl, false, $context);

        return json_decode($questions, true);
    }

    /**
     * @param array $questions
     * @param string $mainTag
     * @return array
     *
     * @see https://api.slack.com/reference/messaging/attachments
     */
    public function convertQuestionsToSlackMessages(array $questions, string $mainTag): array
    {
        $messages = [];
        foreach ($questions['items'] as $question) {
            $message = $this->convertQuestionToSlackMessage($question);
            foreach ($question['tags'] as $tag) {
                if (array_key_exists($tag, $this->slackWebhookUrls[$mainTag])) {
                    $messages[$tag][$question['question_id']] = $message;
                }
            }
        }

        return $messages;
    }

    protected function convertQuestionToSlackMessage(array $question): array
    {
        if ($this->slackMessageStructure === self::SLACK_MESSAGE_STRUCTURE_LEGACY) {
            return $this->convertQuestionToLegacySlackMessage($question);
        } else {
            return $this->convertQuestionToBlockSlackMessage($question);
        }
    }

    /**
     * This is the deprecated structure for creating Slack messages and
     * may be subject to reductions in visibility or functionality.
     *
     * @param array $question
     * @return array[]
     *
     * @see https://api.slack.com/reference/messaging/attachments#legacy_fields
     */
    protected function convertQuestionToLegacySlackMessage(array $question): array
    {
        return [
            'attachments' => [
                [
                    'fallback' => 'New question in StackOverflow: ' . $question['title'],
                    'title' => $question['title'],
                    'title_link' => $question['link'],
                    'thumb_url' => $question['owner']['profile_image'] ?? '',
                    'text' => (new HtmlMrkdwnParser())->parse($question['body']),
                    'color' => '#F2740D',
                    'fields' => [
                        [
                            'title' => 'Tags',
                            'value' => implode(', ', $question['tags'])
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * This is the latest structure for creating Slack messages.
     *
     * @param array $question
     * @return array[]
     *
     * @see https://api.slack.com/reference/messaging/attachments#fields
     */
    protected function convertQuestionToBlockSlackMessage(array $question): array
    {
        return [
            'attachments' => [
                [
                    // The deprecated "fallback" field is displayed in the Slack system notification
                    // and cannot yet be replaced with a current field.
                    'fallback' => 'New question in StackOverflow: ' . $question['title'],
                    'color' => '#F2740D',
                    'blocks' => [
                        [
                            'type' => 'section',
                            'text' => [
                                'type' => 'mrkdwn',
                                'text' => sprintf('*<%s|%s>*', $question['link'], $question['title']),
                            ],
                        ],
                        [
                            'type' => 'section',
                            'text' => [
                                'type' => 'mrkdwn',
                                'text' => (new HtmlMrkdwnParser())->parse($question['body']),
                            ],
                            'accessory' => [
                                'type' => 'image',
                                'image_url' => $question['owner']['profile_image'] ?? '',
                                'alt_text' => $question['owner']['display_name'] ?? '',
                            ]
                        ],
                        [
                            'type' => 'context',
                            'elements' => [
                                [
                                    'type' => 'mrkdwn',
                                    'text' => 'Posted: ' . sprintf('<!date^%s^{date_pretty} at {time}|%s>', $question['creation_date'], date('Y-m-d H:i e', $question['creation_date']))
                                ],
                                [
                                    'type' => 'plain_text',
                                    'text' => 'Tags: ' . implode(', ', $question['tags'])
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $messages
     * @param string $mainTag
     * @return void
     *
     * @see https://api.slack.com/messaging/webhooks
     */
    public function sendMessagesToSlack(array $messages, string $mainTag): void
    {
        foreach ($messages as $tag => $messagesByTag) {
            foreach ($messagesByTag as $message) {
                $curlHandler = curl_init();
                curl_setopt($curlHandler, CURLOPT_URL, $this->slackWebhookUrls[$mainTag][$tag]);
                curl_setopt($curlHandler, CURLOPT_POST, count($message));
                curl_setopt($curlHandler, CURLOPT_POSTFIELDS, json_encode($message));

                curl_exec($curlHandler);

                curl_close($curlHandler);
            }
        }
    }

    /**
     * @return int
     */
    public function getLastExecution(): int
    {
        return (int)@file_get_contents($this->lastExecutionFilename);
    }

    /**
     * @return void
     */
    public function updateLastExecution(): void
    {
        file_put_contents($this->lastExecutionFilename, time());
    }
}
