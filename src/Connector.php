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

    /**
     * @var string
     */
    protected string $stackAppsKey = '';

    /**
     * @var string
     */
    protected string $stackExchangeApiUrl = 'https://api.stackexchange.com/2.3/';

    /**
     * @var array
     */
    protected array $slackWebhookUrls = [];

    /**
     * @return void
     */
    public function loadStackAppsKey()
    {
        $this->stackAppsKey = str_replace("\n", '', file_get_contents($this->getStackAppsKeyFilename()));
    }

    /**
     * @return string
     */
    protected function getStackAppsKeyFilename(): string
    {
        return getenv('keyfile') ?? 'key.txt';
    }

    /**
     * @return void
     */
    public function loadSlackWebHookUrls()
    {
        $this->slackWebhookUrls = parse_ini_file($this->getSlackWebHookUrlsFilename(), true);
    }

    /**
     * @return string
     */
    protected function getSlackWebHookUrlsFilename(): string
    {
        return getenv('hooksfile') ?? 'webhooks.ini';
    }

    /**
     * @return array
     */
    public function getMainTags()
    {
        if (empty($this->slackWebhookUrls)) {
            $this->loadSlackWebHookUrls();
        }
        return array_keys($this->slackWebhookUrls);
    }

    /**
     * @param string $tag
     * @return array|null
     */
    public function fetchLatestQuestionsFromStackOverflow(string $tag): ?array
    {
        $questionsUrl = $this->stackExchangeApiUrl . '/questions?' . http_build_query([
            'site' => 'stackoverflow',
            'filter' => 'withbody',
            'order' => 'asc',
            'tagged' => $tag,
            'key' => $this->stackAppsKey,
            'fromdate' => $this->getLastExecution() ?: time() - 24 * 3600
        ]);
        $questions = file_get_contents('compress.zlib://' . $questionsUrl);

        return json_decode($questions, true);
    }

    /**
     * @param array $questions
     * @param string $mainTag
     * @return array
     */
    public function convertQuestionsToSlackMessages(array $questions, string $mainTag): array
    {
        $messages = [];
        foreach ($questions['items'] as $question) {
            $message = [
                'attachments' => [
                    [
                        'fallback' => 'New question in StackOverflow: ' . $question['title'],
                        'title' => $question['title'],
                        'title_link' => $question['link'],
                        'thumb_url' => $question['owner']['profile_image'] ?? '',
                        'text' => (new HtmlMrkdwnParser())->parse($question['body']),
                        'fields' => [
                            [
                                'title' => 'Tags',
                                'value' => implode(', ', $question['tags'])
                            ]
                        ]
                    ]
                ]
            ];
            foreach ($question['tags'] as $tag) {
                if (array_key_exists($tag, $this->slackWebhookUrls[$mainTag])) {
                    $messages[$tag][$question['question_id']] = $message;
                }
            }
        }

        return $messages;
    }

    /**
     * @param array $messages
     * @param string $mainTag
     * @return void
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
    protected function getLastExecution(): int
    {
        return (int)@file_get_contents($this->getLastExecutionFilename());
    }

    /**
     * @return void
     */
    public function updateLastExecution(): void
    {
        file_put_contents($this->getLastExecutionFilename(), time());
    }

    /**
     * @return string
     */
    protected function getLastExecutionFilename(): string
    {
        return getenv('lastexecutionfile') ?? 'last_execution.txt';
    }
}