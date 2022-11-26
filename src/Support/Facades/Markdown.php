<?php namespace Winter\Storm\Support\Facades;

use Winter\Storm\Support\Facade;

/**
 * @method static string parse(string $markdown)
 * @method static string parseClean(string $markdown)
 * @method static string parseSafe(string $markdown)
 * @method static string parseLine(string $markdown)
 * @method static array getFrontMatter()
 * @method static void setParser(\League\CommonMark\Parser\MarkdownParserInterface $parser)
 * @method static void setRenderer(\League\CommonMark\Renderer\DocumentRendererInterface $renderer)
 * @method static \Winter\Storm\Parse\Markdown enableAttributes()
 * @method static \Winter\Storm\Parse\Markdown enableAutolinking()
 * @method static \Winter\Storm\Parse\Markdown enableFootnotes()
 * @method static \Winter\Storm\Parse\Markdown enableFrontMatter()
 * @method static \Winter\Storm\Parse\Markdown enableHeadingPermalinks()
 * @method static \Winter\Storm\Parse\Markdown enableInlineOnly()
 * @method static \Winter\Storm\Parse\Markdown enableSafeMode()
 * @method static \Winter\Storm\Parse\Markdown enableTaskLists()
 * @method static \Winter\Storm\Parse\Markdown enableTables()
 * @method static \Winter\Storm\Parse\Markdown enableTableOfContents()
 * @method static \Winter\Storm\Parse\Markdown disableAttributes()
 * @method static \Winter\Storm\Parse\Markdown disableAutolinking()
 * @method static \Winter\Storm\Parse\Markdown disableFootnotes()
 * @method static \Winter\Storm\Parse\Markdown disableFrontMatter()
 * @method static \Winter\Storm\Parse\Markdown disableHeadingPermalinks()
 * @method static \Winter\Storm\Parse\Markdown disableInlineOnly()
 * @method static \Winter\Storm\Parse\Markdown disableSafeMode()
 * @method static \Winter\Storm\Parse\Markdown disableTaskLists()
 * @method static \Winter\Storm\Parse\Markdown disableTables()
 * @method static \Winter\Storm\Parse\Markdown disableTableOfContents()
 * @method static \Winter\Storm\Parse\Markdown setAttributes(bool $enabled)
 * @method static \Winter\Storm\Parse\Markdown setAutolinking(bool $enabled)
 * @method static \Winter\Storm\Parse\Markdown setConfig(array $config)
 * @method static \Winter\Storm\Parse\Markdown setFootnotes(bool $enabled)
 * @method static \Winter\Storm\Parse\Markdown setFrontMatter(bool $enabled)
 * @method static \Winter\Storm\Parse\Markdown setHeadingPermalinks(bool $enabled)
 * @method static \Winter\Storm\Parse\Markdown setInlineOnly(bool $enabled)
 * @method static \Winter\Storm\Parse\Markdown setSafeMode(bool $enabled)
 * @method static \Winter\Storm\Parse\Markdown setTaskLists(bool $enabled)
 * @method static \Winter\Storm\Parse\Markdown setTables(bool $enabled)
 * @method static \Winter\Storm\Parse\Markdown setTableOfContents(bool $enabled)
 *
 * @see \Winter\Storm\Parse\Markdown
 */
class Markdown extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static $cached = false;

    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'parse.markdown';
    }
}
