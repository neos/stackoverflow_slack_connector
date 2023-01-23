<?php
declare(strict_types=1);

namespace StackoverflowSlackConnectorTests\Unit;

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

    public function testConvertQuestionsToSlackMessages(): void
    {
        $stackOverflowQuestions = json_decode(<<<'NOWDOC'
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
      "body": "<p>Is the document tree cached in the Neos CMS backend? I have created two pages under &quot;Home&quot;: &quot;Neos CMS&quot; and &quot;Blog&quot; and they are displayed correctly in the main menu of the page preview, but when I click on one of the two page nodes in the document tree, the &quot;Blog&quot; node disappears in the document tree. Only when I click on the &quot;Home&quot; node or the tree refresh button, the &quot;Blog&quot; node temporarily reappears. When flushing the cache via CLI command <code>./flow flow:cache:flush</code> the node becomes permanently visible.</p>\n<p>This behavior is browser independent; normally I use Firefox, just now I used Vivaldi with default settings.</p>\n<p>I used the <a href=\"https://github.com/code-q-web-factory/Neos-Skeleton\" rel=\"nofollow noreferrer\">CodeQ Skeleton</a> as the base distribution, but others have confirmed the behavior for the official <a href=\"https://github.com/neos/neos-base-distribution\" rel=\"nofollow noreferrer\">Neos Base Distribution</a> as well.</p>\n<p>I can provide a bash script that builds up a Neos instance for repeating reproduction of this behaviour.</p>\n<p><strong>Bold</strong> <em>italic</em> <code>inline code</code></p>\n<pre class=\"lang-php prettyprint-override\"><code>$block = &quot;code&quot;;\n</code></pre>\n<ol>\n<li>List item 1</li>\n<li>List item 2</li>\n</ol>\n<ul>\n<li>List item 3</li>\n<li>List item 4</li>\n</ul>\n<ol>\n<li>List item 5</li>\n<li>List item 6</li>\n</ol>\n<ul>\n<li>List item 7</li>\n<li>List item 8</li>\n</ul>\n<h2>Heading</h2>\n<hr />\n<p><div class=\"snippet\" data-lang=\"js\" data-hide=\"false\" data-console=\"true\" data-babel=\"false\">\r\n<div class=\"snippet-code\">\r\n<pre class=\"snippet-code-html lang-html prettyprint-override\"><code>&lt;html&gt;\n&lt;body&gt;Test&lt;/body&gt;\n&lt;/html&gt;</code></pre>\r\n</div>\r\n</div>\r\n</p>\n"
    }
  ],
  "has_more": false,
  "quota_max": 10000,
  "quota_remaining": 9940
}
NOWDOC, true);

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

*Bold* _italic_ `inline code`

```
$block = "code";
```

1. List item 1
2. List item 2

- List item 3
- List item 4

1. List item 5
2. List item 6

- List item 7
- List item 8

*Heading*

---

```
&lt;html&gt;
&lt;body&gt;Test&lt;/body&gt;
&lt;/html&gt;
```
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

        $this->addFileToTestDirectory('webhooks.ini', <<<'NOWDOC'
[neoscms]
    neoscms = https://hooks.slack.com/services/segment1/segment2/segment3
NOWDOC);
        $this->setEnvVar('hooksfile', $this->getPathOfTestDirectoryFile('webhooks.ini'));

        $connector = new Connector();
        $connector->loadSlackWebhookUrls();
        $actual = $connector->convertQuestionsToSlackMessages($stackOverflowQuestions, 'neoscms');
        $this->assertEquals($expected, $actual);
    }
}
