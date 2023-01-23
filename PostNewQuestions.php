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

$connector = new Connector();
$connector->loadStackAppsKey();
$connector->loadSlackWebHookUrls();
$mainTags = $connector->getMainTags();
foreach ($mainTags as $mainTag) {
    $questions = $connector->fetchLatestQuestionsFromStackOverflow($mainTag);
    $messages = $connector->convertQuestionsToSlackMessages($questions, $mainTag);
    $connector->sendMessagesToSlack($messages, $mainTag);
}

if (!empty($messages)) {
    $connector->updateLastExecution();
}
