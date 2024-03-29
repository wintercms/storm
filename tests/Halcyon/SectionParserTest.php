<?php

use Winter\Storm\Halcyon\Processors\SectionParser;

class SectionParserTest extends TestCase
{
    public function testParse()
    {
        // Test a single section
        $result = SectionParser::parse("this is a twig content");
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertEmpty($result["settings"]);
        $this->assertNull($result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals("this is a twig content", $result["markup"]);

        // Test two sections
        $result = SectionParser::parse("url = \"/blog/post/\" \n==\n this is a twig content");
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["markup"]);
        $this->assertNotNull($result["settings"]);
        $this->assertNull($result["code"]);
        $this->assertEquals("this is a twig content", $result["markup"]);
        $this->assertIsArray($result["settings"]);
        $this->assertArrayHasKey("url", $result["settings"]);
        $this->assertEquals("/blog/post/", $result["settings"]["url"]);

        // Test three sections
        $result = SectionParser::parse("url = \"/blog/post/\"\n[section]\nindex = value \n==\n \$var = 23; \n phpinfo(); \n==\n this is a twig content");
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["settings"]);
        $this->assertNotNull($result["markup"]);
        $this->assertNotNull($result["code"]);
        $this->assertEquals("this is a twig content", $result["markup"]);
        $this->assertIsArray($result["settings"]);
        $this->assertArrayHasKey("url", $result["settings"]);
        $this->assertEquals("/blog/post/", $result["settings"]["url"]);
        $this->assertStringContainsString("\$var = 23;", $result["code"]);
        $this->assertStringContainsString("phpinfo();", $result["code"]);

        $this->assertArrayHasKey("section", $result["settings"]);
        $this->assertIsArray($result["settings"]["section"]);
        $this->assertArrayHasKey("index", $result["settings"]["section"]);
        $this->assertEquals("value", $result["settings"]["section"]["index"]);

        // Test > 3 sections
        // Test > 3 sections
        $result = SectionParser::parse(
            'title = "test"' . PHP_EOL .
            'url = "/test"' . PHP_EOL .
            '==' . PHP_EOL .
            '<?php' . PHP_EOL .
            'function onStart()' . PHP_EOL .
            '{' . PHP_EOL .
            '}' . PHP_EOL .
            '==' . PHP_EOL .
            'Start of markup content' . PHP_EOL .
            '==' . PHP_EOL .
            'random separator detected'
        );
        $this->assertSame($result['markup'], 'Start of markup content' . PHP_EOL . '==' . PHP_EOL . 'random separator detected');

        // Test zero sections
        $result = SectionParser::parse("");
        $this->assertCount(3, $result);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertEmpty($result["settings"]);
        $this->assertNull($result["markup"]);
        $this->assertNull($result["code"]);
        $this->assertEquals("", $result["markup"]);

        // Test doesn't break Markdown single section
        $result = SectionParser::parse("This is a header\n================\n\nThis is a paragraph");
        $this->assertCount(3, $result);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertEmpty($result["settings"]);
        $this->assertNull($result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals("This is a header\n================\n\nThis is a paragraph", $result["markup"]);

        // Test doesn't break Markdown two sections
        $result = SectionParser::parse("url = \"/blog/post\"\n==\nThis is a header\n================\n\nThis is a paragraph");
        $this->assertCount(3, $result);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["settings"]);
        $this->assertArrayHasKey("url", $result["settings"]);
        $this->assertEquals("/blog/post", $result["settings"]["url"]);
        $this->assertNull($result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals("This is a header\n================\n\nThis is a paragraph", $result["markup"]);

        // Test doesn't break Markdown three sections
        $result = SectionParser::parse("url = \"/blog/post\"\n==\n\$var = 23; \n phpinfo();\n==\nThis is a header\n================\n\nThis is a paragraph");
        $this->assertCount(3, $result);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["settings"]);
        $this->assertArrayHasKey("url", $result["settings"]);
        $this->assertEquals("/blog/post", $result["settings"]["url"]);
        $this->assertNotNull($result["code"]);
        $this->assertStringContainsString("\$var = 23;", $result["code"]);
        $this->assertStringContainsString("phpinfo();", $result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals("This is a header\n================\n\nThis is a paragraph", $result["markup"]);
    }

    public function testParseOffset()
    {
        // Test three sections
        $content = <<<ESC
setting = "test"
==
function onStart() { // Line 3

}
==
<p>Line 7</p>
ESC;

        $result = SectionParser::parseOffset($content);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["settings"]);
        $this->assertNotNull($result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals(1, $result["settings"]);
        $this->assertEquals(3, $result["code"]);
        $this->assertEquals(7, $result["markup"]);

        // Test two sections
        $content = <<<ESC
setting = "test"
another = "setting"
foo = "bar"
==
<p>Line 5</p>
ESC;
        $result = SectionParser::parseOffset($content);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["markup"]);
        $this->assertNotNull($result["settings"]);
        $this->assertNull($result["code"]);
        $this->assertEquals(1, $result["settings"]);
        $this->assertEquals(5, $result["markup"]);

        // Test two sections with white space
        $content = <<<ESC


line = "Line 3"
another = "setting"
foo = "bar"
==





<p>Line 12</p>
ESC;
        $result = SectionParser::parseOffset($content);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["markup"]);
        $this->assertNotNull($result["settings"]);
        $this->assertNull($result["code"]);
        $this->assertEquals(3, $result["settings"]);
        $this->assertEquals(12, $result["markup"]);

        // Test one section
        $content = <<<ESC
<p>Line 1</p>
<p>Line 2</p>
<p>Line 3</p>
ESC;
        $result = SectionParser::parseOffset($content);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["markup"]);
        $this->assertNull($result["settings"]);
        $this->assertNull($result["code"]);
        $this->assertEquals(1, $result["markup"]);


        // Test empty PHP
        $content = <<<ESC
setting = "test"
==
==
<p>Line 4</p>
ESC;

        $result = SectionParser::parseOffset($content);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["settings"]);
        $this->assertNotNull($result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals(1, $result["settings"]);
        $this->assertEquals(3, $result["code"]);
        $this->assertEquals(4, $result["markup"]);

        // Test with PHP tags
        $content = <<<ESC
setting = "test"
another = "setting"
==
<?
function onStart() {

}
?>
==
<p>Line 10</p>
ESC;

        $result = SectionParser::parseOffset($content);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["settings"]);
        $this->assertNotNull($result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals(1, $result["settings"]);
        $this->assertEquals(5, $result["code"]);
        $this->assertEquals(10, $result["markup"]);

        // Test with PHP tags and whitespace
        $content = <<<ESC
setting = "test"
another = "setting"
foo = "bar"
==







<?php
function onStart() { // Line 13

}
?>
==
<p>Line 18</p>
ESC;

        $result = SectionParser::parseOffset($content);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["settings"]);
        $this->assertNotNull($result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals(1, $result["settings"]);
        $this->assertEquals(13, $result["code"]);
        $this->assertEquals(18, $result["markup"]);

        // Test with PHP tags and whitespace both sides
        $content = <<<ESC
setting = "test"
another = "setting"
foo = "bar"
==







<?php







function onStart() { // Line 20

}
?>
==
<p>Line 25</p>
ESC;

        $result = SectionParser::parseOffset($content);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["settings"]);
        $this->assertNotNull($result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals(1, $result["settings"]);
        $this->assertEquals(20, $result["code"]);
        $this->assertEquals(25, $result["markup"]);

        // Test with whitespace on PHP and Twig
        $content = <<<ESC
setting = "test"
another = "setting"
foo = "bar"
==



function onStart() { // Line 8

}
==







<p>Line 19</p>
ESC;

        $result = SectionParser::parseOffset($content);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["settings"]);
        $this->assertNotNull($result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals(1, $result["settings"]);
        $this->assertEquals(8, $result["code"]);
        $this->assertEquals(19, $result["markup"]);

        // Test namespaces
        $content = <<<ESC

line = "Line 2"
setting = "test"
==

use Winter\Storm\Support\Str; // This will be removed (-1 line)
use Winter\Storm\Flash\FlashBag; // This will be removed (-1 line)

function onStart() { // Line 7

    use Winter\Storm\Support\Str; // And placed here
    use Winter\Storm\Flash\FlashBag; // And placed here

}
==
<p>Line 16</p>
ESC;

        $result = SectionParser::parseOffset($content);
        $this->assertArrayHasKey("settings", $result);
        $this->assertArrayHasKey("code", $result);
        $this->assertArrayHasKey("markup", $result);
        $this->assertNotNull($result["settings"]);
        $this->assertNotNull($result["code"]);
        $this->assertNotNull($result["markup"]);
        $this->assertEquals(2, $result["settings"]);
        $this->assertEquals(7, $result["code"]);
        $this->assertEquals(16, $result["markup"]);
    }
}
