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
        $questionBody = <<<'NOWDOC'
<p>Block text with <a href="https://www.neos.io/" title="Neos CMS - Open Source Content Application Framework" rel="nofollow noreferrer">link</a>.</p>
<p>Block text with &quot;quotation&quot;.</p>
<p>Block text with <code>$inline = &quot;code&quot;;</code>.</p>
<p>Block text with <strong>bold</strong> and <em>italic</em>.</p>
<pre class="lang-php prettyprint-override"><code>$block = &quot;code&quot;;
</code></pre>
<ol>
<li>Numbered list item 1</li>
<li>Numbered list item 2</li>
</ol>
<ul>
<li>List item 3</li>
<li>List item 4</li>
</ul>
<h1>Heading 1</h1>
<h2>Heading 2</h2>
<h3>Heading 3</h3>
<hr />
<p><div class="snippet" data-lang="js" data-hide="false" data-console="true" data-babel="false">
<div class="snippet-code">
<pre class="snippet-code-html lang-html prettyprint-override"><code>&lt;html&gt;
&lt;body&gt;Code snippet&lt;/body&gt;
&lt;/html&gt;</code></pre>
</div>
</div>
</p>
NOWDOC;

        $expected = <<<'NOWDOC'
Block text with <https://www.neos.io/|link>.

Block text with "quotation".

Block text with `$inline = "code";`.

Block text with *bold* and _italic_.

```
$block = "code";
```

1. Numbered list item 1
2. Numbered list item 2

- List item 3
- List item 4

*Heading 1*

*Heading 2*

*Heading 3*

---

```
&lt;html&gt;
&lt;body&gt;Code snippet&lt;/body&gt;
&lt;/html&gt;
```
NOWDOC;

        $actual = (new HtmlMrkdwnParser())->parse($questionBody);
        $this->assertEquals($expected, $actual);
    }
}
