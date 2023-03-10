<?php
declare(strict_types=1);

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

use StackoverflowSlackConnector\Connector;

// At the moment, we refrain from using Composer in production and
// therefore load the classes manually.
require 'src/Connector.php';
require 'src/HtmlMrkdwnParser.php';

$slackWebhookUrlsFilename = getenv('hooksfile') ?: 'webhooks.ini';
$slackMessageStructure = getenv('messagestructure') ?: Connector::SLACK_MESSAGE_STRUCTURE_BLOCK;
$stackAppsKeyFilename = getenv('keyfile') ?: 'key.txt';
$lastExecutionFilename = getenv('lastexecutionfile') ?: 'last_execution.txt';

$connector = new Connector();
$connector->setSlackWebhookUrls(parse_ini_file($slackWebhookUrlsFilename, true));
$connector->setSlackMessageStructure($slackMessageStructure);
$connector->setStackAppsKey(@file_get_contents($stackAppsKeyFilename) ?: '');
$connector->setLastExecutionFilename($lastExecutionFilename);
$mainTags = $connector->getMainTags();
$fromDate = $connector->getLastExecution() ?: time() - 24 * 3600;
foreach ($mainTags as $mainTag) {
    $questions = $connector->fetchQuestionsFromStackOverflow($mainTag, $fromDate);
    $messages = $connector->convertQuestionsToSlackMessages($questions, $mainTag);
    $connector->sendMessagesToSlack($messages, $mainTag);
}
$connector->updateLastExecution();
