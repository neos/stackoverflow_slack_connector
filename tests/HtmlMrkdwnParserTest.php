<?php
declare(strict_types=1);

namespace StackoverflowSlackConnectorTests;

use StackoverflowSlackConnector\HtmlMrkdwnParser;

final class HtmlMrkdwnParserTest extends AbstractTestCase
{
    /**
     * @group unit
     */
    public function testParse(): void
    {
        $stackOverflowQuestionBody = <<<'NOWDOC'
<p>Is the document tree cached in the Neos CMS backend? I have created two pages under &quot;Home&quot;: &quot;Neos CMS&quot; and &quot;Blog&quot; and they are displayed correctly in the main menu of the page preview, but when I click on one of the two page nodes in the document tree, the &quot;Blog&quot; node disappears in the document tree. Only when I click on the &quot;Home&quot; node or the tree refresh button, the &quot;Blog&quot; node temporarily reappears. When flushing the cache via CLI command <code>./flow flow:cache:flush</code> the node becomes permanently visible.</p>
<p>This behavior is browser independent; normally I use Firefox, just now I used Vivaldi with default settings.</p>
<p>I used the <a href="https://github.com/code-q-web-factory/Neos-Skeleton" rel="nofollow noreferrer">CodeQ Skeleton</a> as the base distribution, but others have confirmed the behavior for the official <a href="https://github.com/neos/neos-base-distribution" rel="nofollow noreferrer">Neos Base Distribution</a> as well.</p>
<p>I can provide a bash script that builds up a Neos instance for repeating reproduction of this behaviour.</p>
<p><strong>Bold</strong> <em>italic</em> <code>inline code</code></p>
<pre class="lang-php prettyprint-override"><code>$block = &quot;code&quot;;
</code></pre>
<ol>
<li>List item 1</li>
<li>List item 2</li>
</ol>
<ul>
<li>List item 3</li>
<li>List item 4</li>
</ul>
<ol>
<li>List item 5</li>
<li>List item 6</li>
</ol>
<ul>
<li>List item 7</li>
<li>List item 8</li>
</ul>
<h2>Heading</h2>
<hr />
<p><div class="snippet" data-lang="js" data-hide="false" data-console="true" data-babel="false">
<div class="snippet-code">
<pre class="snippet-code-html lang-html prettyprint-override"><code>&lt;html&gt;
&lt;body&gt;Test&lt;/body&gt;
&lt;/html&gt;</code></pre>
</div>
</div>
</p>
NOWDOC;

        $expected = <<<'NOWDOC'
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
NOWDOC;

        $actual = (new HtmlMrkdwnParser())->parse($stackOverflowQuestionBody);
        $this->assertEquals($expected, $actual);
    }
}
