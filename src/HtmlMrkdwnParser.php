<?php
declare(strict_types=1);

namespace StackoverflowSlackConnector;

/**
 * Parsing HTML markup in Slack's Markdown derivative "mrkdwn".
 *
 * @see https://api.slack.com/reference/surfaces/formatting
 */
class HtmlMrkdwnParser
{
    function parse(string $html): string
    {
        $result = preg_replace_callback_array(
            [
                '#<h[123456]>(.*?)</h[123456]>#s' => function ($match) {
                    return sprintf("*%s*\n", $match[1]);
                },
                '#<a[^>]*href="([^"]*)"[^>]*>([^<]*)</a>#' => function ($match) {
                    return sprintf("<%s|%s>", $match[1], $match[2]);
                },
                '#<div[^>]*class="snippet"[^>]*>\s*<div[^>]*>\s*<pre[^>]*>\s*<code[^>]*>([^<]*)</code>\s*</pre>\s*</div>\s*</div>#' => function ($match) {
                    return sprintf("```\n%s\n```", trim($match[1]));
                },
                '#<pre[^>]*>\s*<code[^>]*>([^<]*)</code>\s*</pre>#' => function ($match) {
                    return sprintf("```\n%s\n```", trim($match[1]));
                },
                '#<ol[^>]*>(.*?)</ol>#s' => function ($match) {
                    $itemNumber = 0;
                    return preg_replace_callback('#\s*<li>(.*?)</li>\s*#s', function ($subMatch) use (&$itemNumber) {
                        return sprintf("%d. %s\n", ++$itemNumber, $subMatch[1]);
                    }, $match[1]);
                },
                '#<ul[^>]*>(.*?)</ul>#s' => function ($match) {
                    return preg_replace('#\s*<li>(.*?)</li>\s*#s', "- $1\n", $match[1]);
                },
            ],
            $html
        );

        $result = str_replace(
            [' & ', '<p>', '</p>', '<code>', '</code>', '<strong>', '</strong>', '<em>', '</em>', '<hr />', '&quot;', '&#039;'],
            [' &amp; ', '', "\n", '`', '`', '*', '*', '_', '_', "---\n", '"', "'"],
            $result
        );

        return rtrim($result);
    }
}