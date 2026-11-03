<?php

use Winter\Storm\Html\HtmlBuilder;

class HtmlBuilderTest extends TestCase
{
    public function testStrip()
    {
        $result = with(new HtmlBuilder)->strip('<p>hello</p>');
        $this->assertEquals('hello', $result);
    }

    public function testLimit()
    {
        $result = with(new HtmlBuilder)->limit('<p>The quick brown fox jumped over the lazy dog</p>', 10);
        $this->assertEquals('<p>The quick ...</p>', $result);

        $result = with(new HtmlBuilder)->limit('<p>The quick brown fox’s jumped over the lazy dog</p>', 25, '!!!');
        $this->assertEquals('<p>The quick brown fox’s jum!!!</p>', $result);

        $result = with(new HtmlBuilder)->limit("<p>The quick brown fox jumped over the lazy dog</p><p>The quick brown fox jumped over the lazy dog</p>", 50);
        $this->assertEquals('<p>The quick brown fox jumped over the lazy dog</p><p>The qu...</p>', $result);

        $result = with(new HtmlBuilder)->limit(trim("
            <p>The quick brown fox jumped over the lazy dog</p>\n<p>The quick brown fox jumped over the lazy dog</p>
        "), 60);

        $this->assertEquals(trim("
            <p>The quick brown fox jumped over the lazy dog</p>\n<p>The quick brown...</p>
        "), $result);

        $result = with(new HtmlBuilder)->limit(trim("
            <p>The quick brown fox jumped over the lazy dog</p>\r\n<p>The quick brown fox jumped over the lazy dog</p>
        "), 60);

        $this->assertEquals(trim("
            <p>The quick brown fox jumped over the lazy dog</p>\r\n<p>The quick brown...</p>
        "), $result);

        $result = with(new HtmlBuilder)->limit(trim("
            <p>The quick brown fox jumped          over the lazy dog</p>\r\n<p>The quick brown fox jumped over the lazy dog</p>
        "), 60);

        $this->assertEquals(trim("
            <p>The quick brown fox jumped          over the lazy dog</p>\r\n<p>The quick brown...</p>
        "), $result);

        $result = with(new HtmlBuilder)->limit(trim("
            <p>The quick brown fox jumped over&nbsp;&nbsp;the lazy dog</p>\n<p>The quick brown fox jumped over the lazy dog</p>
        "), 60);

        $this->assertEquals(trim("
            <p>The quick brown fox jumped over&nbsp;&nbsp;the lazy dog</p>\n<p>The quick brow...</p>
        "), $result);
    }

    public function testLimitEncoding()
    {
        $result = with(new HtmlBuilder)->limit('<p>Er hörte leise Schritte hinter sich. Das bedeutete nichts Gutes.</p>', 10);
        $this->assertEquals('<p>Er hörte l...</p>', $result);
    }

    public function testClean()
    {
        $result = with(new HtmlBuilder)->clean('<script>window.location = "http://google.com"</script>');
        $this->assertEquals('window.location = "http://google.com"', $result);

        $result = with(new HtmlBuilder)->clean('<span style="width: expression(alert(\'Ping!\'));"></span>');
        $this->assertEquals('<span ></span>', $result);

        $result = with(new HtmlBuilder)->clean('<a href="javascript: alert(\'Ping!\');">Test</a>');
        $this->assertEquals('<a href="nojavascript... alert(\'Ping!\');">Test</a>', $result);

        $result = with(new HtmlBuilder)->clean('<a href=" &#14;  javascript: alert(\'Ping!\');">Test</a>');
        $this->assertEquals('<a href="nojavascript... alert(\'Ping!\');">Test</a>', $result);

        $result = with(new HtmlBuilder)->clean('<a href=" &#14  javascript: alert(\'Ping!\');">Test</a>');
        $this->assertEquals('<a href="nojavascript... alert(\'Ping!\');">Test</a>', $result);

        $result = with(new HtmlBuilder)->clean('<a href=" &#14;;  javascript: alert(\'Ping!\');">Test</a>');
        $this->assertEquals('<a href="nojavascript... alert(\'Ping!\');">Test</a>', $result);
    }

    public static function unsafeAttributeProvider()
    {
        return [
            'unquoted value'                       => ['<img src=x onerror=alert(1)>', 'onerror'],
            'double quoted value'                  => ['<img src="x" onerror="alert(1)">', 'onerror'],
            'greater than in an earlier value'     => ['<img alt=">" src="x" onerror="alert(1)">', 'onerror'],
            'greater than, unquoted handler'       => ['<img src=">" onerror=alert(1)>', 'onerror'],
            'greater than, single quotes'          => ["<img alt='>' src='x' onerror='alert(1)'>", 'onerror'],
            'greater than, no separator'           => ['<img src=">"onerror=alert(1)>', 'onerror'],
            'no separator'                         => ['<img src="x"onerror=alert(1)>', 'onerror'],
            'slash separator'                      => ['<img src="x"/onerror=alert(1)>', 'onerror'],
            'quote inside an unquoted value'       => ['<img alt=a"b onerror=alert(1)>', 'onerror'],
            'unterminated tag'                     => ['<img src=x onerror=alert(1)//', 'onerror'],
            'unterminated tag, greater than'       => ['<img alt=">" src=x onerror=alert(1)', 'onerror'],
            'upper case'                           => ['<IMG SRC=">" ONERROR=ALERT(1)>', 'onerror'],
            'newline separator'                    => ["<img alt=\">\" src=x\nonerror=alert(1)>", 'onerror'],
            'spaces around the equals sign'        => ['<img alt=">" src=x onerror = alert(1)>', 'onerror'],
            'greater than in a later value'        => ['<img src=x title=">" onerror=alert(1)>', 'onerror'],
            'two handlers'                         => ['<img alt=">" src=x onload=alert(1) onerror=alert(2)>', 'onerror'],
            'handler before other attributes'      => ['<img alt=">" onerror=alert(1) src=x>', 'onerror'],
            'nested quotes'                        => ['<img alt=\'">\' src=x onerror=alert(1)>', 'onerror'],
            'svg element'                          => ['<svg id=">" onload=alert(1)></svg>', 'onload'],
            'details element'                      => ['<details alt=">" ontoggle=alert(1) open></details>', 'ontoggle'],
            'xmlns declaration'                    => ['<img alt=">" xmlns="x" src=x>', 'xmlns'],
            'namespaced xmlns declaration'         => ['<img alt=">" xmlns:xlink="x" src=x>', 'xmlns'],
            'inline style'                         => ['<span alt=">" style="width: expression(alert(1))"></span>', 'style'],
            'equals sign before a name'            => ['<img =foo onerror=alert(1)>', 'onerror'],
            'bare equals sign'                     => ['<img = onerror=alert(1)>', 'onerror'],
            'slash then equals sign'               => ['<img /=foo onerror=alert(1)>', 'onerror'],
            'repeated equals signs'                => ['<img ==foo onerror=alert(1)>', 'onerror'],
        ];
    }

    /**
     * @dataProvider unsafeAttributeProvider
     */
    public function testCleanRemovesUnsafeAttributes($html, $attribute)
    {
        $result = with(new HtmlBuilder)->clean($html);

        $this->assertStringNotContainsStringIgnoringCase($attribute, $result);
    }

    public static function safeMarkupProvider()
    {
        return [
            'heading'                          => ["# Hello\n"],
            'emphasis'                         => ["**bold** and _italic_\n"],
            'link with an ampersand'           => ["[x](https://example.com/a?b=1&c=2)\n"],
            'table'                            => ["| a | b |\n|---|---|\n| 1 | 2 |\n"],
            'list'                             => ["- one\n- two\n"],
            'block quote'                      => ["> quoted text\n"],
            'code fence'                       => ["```php\n<?php echo 'hi';\n```\n"],
            'inline code'                      => ['use `onerror=alert(1)` carefully'],
            'prose mentioning an attribute'    => ['Set onboarding=true in the config.'],
            'prose mentioning a handler'       => ['The onclick= attribute is removed.'],
            'inline html'                      => ['some <b>bold</b> and <em>italic</em> text'],
            'greater than in an attribute'     => ['<img alt="a > b" src="x.png">'],
            'greater than in a title'          => ['<a href="/x" title="a > b">x</a>'],
            'path beginning with on'           => ['<a href="https://example.com/one=1">x</a>'],
            'attribute text inside a value'    => ['<img alt="x onerror=alert(1)" src="y.png">'],
            'data attributes'                  => ['<div data-one="1" data-two="2">x</div>'],
            'framework bound attributes'       => ['<button v-on:click="go" x-on:click="go" @click="go">x</button>'],
            'several attributes'               => ['<img src="a.png" alt="a" title="t" width="10">'],
            'self closing tags'                => ['<br /><hr />'],
            'html entities'                    => ['a &amp; b &lt; c'],
        ];
    }

    /**
     * @dataProvider safeMarkupProvider
     */
    public function testCleanPreservesSafeMarkup($html)
    {
        $this->assertEquals($html, with(new HtmlBuilder)->clean($html));
    }

    public function testCleanKeepsTheAttributesSurroundingARemovedOne()
    {
        $result = with(new HtmlBuilder)->clean('<img alt=">" onerror=alert(1) src="y.png" title="t">');

        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringContainsString('alt=">"', $result);
        $this->assertStringContainsString('src="y.png"', $result);
        $this->assertStringContainsString('title="t"', $result);
    }
}
