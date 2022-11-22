<?php

use Winter\Storm\Support\Facades\Markdown;

class MarkdownTest extends TestCase
{
    /**
     * @dataProvider markdownData
     * @param string $name
     * @param string $markdown
     * @param string $html
     * @return void
     */
    public function testParse($name, $markdown, $html)
    {
        $this->assertEquals($html, $this->removeLineEndings(Markdown::parse($markdown)), 'Markdown test case "' . $name . '" failed');
    }

    public function testParseSafe()
    {
        $markdown = file_get_contents(dirname(__DIR__) . '/fixtures/markdown/code_block.md');
        $html = $this->removeLineEndings(file_get_contents(dirname(__DIR__) . '/fixtures/markdown/code_block_disabled.html'));

        $this->assertEquals($html, $this->removeLineEndings(Markdown::parseSafe($markdown)));
    }

    public function testDisableAndEnableMethods()
    {
        $parser = Markdown::disableAutolinking()->disableTables();

        $markdown = file_get_contents(dirname(__DIR__) . '/fixtures/markdown/table_inline_markdown.md');
        $html = $this->removeLineEndings(file_get_contents(dirname(__DIR__) . '/fixtures/markdown/table_inline_markdown_disabled.html'));

        $this->assertEquals($html, $this->removeLineEndings($parser->parse($markdown)));

        // Re-enable tables and autolinking
        $parser->enableAutolinking()->enableTables();

        $html = $this->removeLineEndings(file_get_contents(dirname(__DIR__) . '/fixtures/markdown/table_inline_markdown.html'));

        $this->assertEquals($html, $this->removeLineEndings($parser->parse($markdown)));
    }

    public function testFrontMatter()
    {
        $parser = Markdown::enableFrontMatter();

        $markdown = file_get_contents(dirname(__DIR__) . '/fixtures/markdown/front_matter.md');
        $html = $this->removeLineEndings(file_get_contents(dirname(__DIR__) . '/fixtures/markdown/front_matter.html'));

        $this->assertEquals($html, $this->removeLineEndings($parser->parse($markdown)));
    }

    /**
     * Data provider to provide test cases for Markdown parsing.
     *
     * @return array
     */
    public function markdownData()
    {
        $dirName = dirname(__DIR__) . '/fixtures/markdown';
        $dir = new DirectoryIterator($dirName);

        foreach ($dir as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $name = $file->getBasename('.md');

                if ($name === 'README') {
                    continue;
                }

                $markdown = file_get_contents($dirName . DIRECTORY_SEPARATOR . $name . '.md');
                $html = $this->removeLineEndings(file_get_contents($dirName . DIRECTORY_SEPARATOR . $name . '.html'));

                yield [$name, $markdown, $html];
            }
        }
    }

    protected function removeLineEndings(string $text)
    {
        return str_replace(["\r\n", "\r", "\n"], '', $text);
    }
}
