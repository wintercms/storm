<?php namespace Winter\Storm\Parse;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Parser\MarkdownParserInterface;
use League\CommonMark\Renderer\DocumentRendererInterface;
use League\CommonMark\Renderer\HtmlRenderer;
use Winter\Storm\Parse\Markdown\CommonMarkCoreExtension;
use Winter\Storm\Parse\Markdown\StrikethroughExtension;
use Winter\Storm\Support\Facades\Event;

/**
 * Markdown parser.
 *
 * This parser allows the parsing and interpretation of Markdown content into raw HTML, as well
 * as extracting metadata from the Markdown content as necessary.
 *
 * This utility has been significantly rewritten to take advantage of the CommonMark library by
 * The PHP League, and more closely adheres to the CommonMark specification, with GitHub-flavored
 * Markdown support.
 *
 * @author Alexey Bobkov, Samuel Georges (Original implementation based on Parsedown)
 * @author Ben Thomson <git@alfreido.com> (Rewritten implementation using CommonMark)
 *
 * @method static enableAttributes()
 * @method static enableAutolinking()
 * @method static enableFootnotes()
 * @method static enableFrontMatter()
 * @method static enableHeadingPermalinks()
 * @method static enableInlineOnly()
 * @method static enableSafeMode()
 * @method static enableTaskLists()
 * @method static enableTables()
 * @method static enableTableOfContents()
 * @method static disableAttributes()
 * @method static disableAutolinking()
 * @method static disableFootnotes()
 * @method static disableFrontMatter()
 * @method static disableHeadingPermalinks()
 * @method static disableInlineOnly()
 * @method static disableSafeMode()
 * @method static disableTaskLists()
 * @method static disableTables()
 * @method static disableTableOfContents()
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
 **/
class Markdown
{
    use \Winter\Storm\Support\Traits\Emitter;

    /**
     * Enables the parsing of attributes for block-level and inline content.
     */
    public bool $attributes = false;

    /**
     * Enables autolinking of URLs and email addresses in Markdown content.
     */
    public bool $autolinking = true;

    /**
     * Enables the parsing and generation of footnotes.
     */
    public bool $footnotes = false;

    /**
     * Enables the parsing of front matter (metadata).
     */
    public bool $frontMatter = false;

    /**
     * Enables the generation of permalinks for each heading.
     */
    public bool $headingPermalinks = false;

    /**
     * Enables inline-only formatting. This is used for rendering a single line of Markdown.
     */
    public bool $inlineOnly = false;

    /**
     * Enables safe mode - disables certain HTML tags.
     */
    public bool $safeMode = false;

    /**
     * Enables the parsing and generation of task lists.
     */
    public bool $taskLists = false;

    /**
     * Enables the generation of tables.
     */
    public bool $tables = true;

    /**
     * Enables the generation of a table of contents.
     */
    public bool $tableOfContents = false;

    /**
     * Custom configuration for the CommonMark environment.
     */
    public array $config = [];

    /**
     * Extracted front matter (metadata) from the Markdown content.
     */
    protected ?array $frontMatterData = null;

    /**
     * The Markdown parser class to use.
     */
    protected ?string $parserClass = null;

    /**
     * The HTML renderer class to use.
     */
    protected ?string $rendererClass = null;

    /**
     * Constructor.
     */
    final public function __construct()
    {
    }

    /**
     * Parse Markdown content and render as HTML.
     */
    public function parse(string $markdown): string
    {
        $environment = $this->createEnvironment();

        if ($this->frontMatter) {
            $markdown = $this->extractFrontMatter($markdown);
        }

        return $this->parseInternal($environment, $markdown);
    }

    /**
     * Parse Markdown content and render as HTML, with unsafe tags disabled.
     *
     * This will prevent tags such as <script>, <embed>, <iframe> from being rendered in the HTML
     * output.
     */
    public function parseClean(string $markdown): string
    {
        $this->safeMode = true;
        $environment = $this->createEnvironment();

        if ($this->frontMatter) {
            $markdown = $this->extractFrontMatter($markdown);
        }

        return $this->parseInternal($environment, $markdown);
    }

    /**
     * Parse Markdown content and render as HTML, with indented code blocks disabled.
     */
    public function parseSafe(string $markdown): string
    {
        $this->config = array_replace_recursive($this->config, [
            'commonmark' => [
                'enable_indented_code_blocks' => false,
            ],
        ]);
        $environment = $this->createEnvironment();

        if ($this->frontMatter) {
            $markdown = $this->extractFrontMatter($markdown);
        }

        return $this->parseInternal($environment, $markdown);
    }

    /**
     * Parse a single line
     */
    public function parseLine(string $markdown): string
    {
        $this->safeMode = true;
        $this->inlineOnly = true;
        $environment = $this->createEnvironment();
        return $this->parseInternal($environment, $markdown);
    }

    /**
     * Creates a CommonMark environment for each parse.
     *
     * The environment will also be extended with the necessary extensions that are needed to
     * support the features enabled for this instance of the parser.
     */
    protected function createEnvironment(): Environment
    {
        $config = array_replace($this->getDefaultConfig(), $this->config);

        $environment = new Environment($config);

        if (!$this->inlineOnly) {
            // Add default extensions
            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new StrikethroughExtension);

            // Add extensions as necessary
            if ($this->attributes) {
                $environment->addExtension(new AttributesExtension);
            }
            if ($this->autolinking) {
                $environment->addExtension(new AutolinkExtension);
            }
            if ($this->footnotes) {
                $environment->addExtension(new FootnoteExtension);
            }
            if ($this->frontMatter) {
                $environment->addExtension(new FrontMatterExtension);
            }
            if ($this->headingPermalinks) {
                $environment->addExtension(new HeadingPermalinkExtension);
            }
            if ($this->safeMode) {
                $environment->addExtension(new DisallowedRawHtmlExtension);
            }
            if ($this->taskLists) {
                $environment->addExtension(new TaskListExtension);
            }
            if ($this->tables) {
                $environment->addExtension(new TableExtension);
            }
            if ($this->tableOfContents) {
                $environment->addExtension(new TableOfContentsExtension);
            }
        } else {
            // Set up a special environment for inline-only mode
            $environment->addExtension(new InlinesOnlyExtension);
            $environment->addExtension(new StrikethroughExtension);

            if ($this->attributes) {
                $environment->addExtension(new AttributesExtension);
            }
            if ($this->autolinking) {
                $environment->addExtension(new AutolinkExtension);
            }
            if ($this->safeMode) {
                $environment->addExtension(new DisallowedRawHtmlExtension);
            }
        }

        return $environment;
    }

    /**
     * Returns the default configuration for the Markdown environment.
     *
     * @return array
     */
    protected function getDefaultConfig(): array
    {
        return [];
    }

    /**
     * Internal method for parsing
     */
    protected function parseInternal(Environment $environment, string $markdown): string
    {
        $data = new MarkdownData($markdown);

        $this->fireEvent('beforeParse', [$data, $environment], false);
        Event::fire('markdown.beforeParse', [$data, $environment], false);

        $markdown = $data->text;

        // Parse the Markdown into a document (abstract syntax tree)
        $parser = $this->getParser($environment);
        $document = $parser->parse($markdown);

        $this->fireEvent('beforeRender', [$document, $environment], false);
        Event::fire('markdown.beforeRender', [$document, $environment], false);

        // Render the AST as a HTML document
        $renderer = $this->getRenderer($environment);
        $rendered = $renderer->renderDocument($document);

        $data->text = $rendered;

        // The markdown.parse gets passed both the original
        // input and the result so far.
        $this->fireEvent('parse', [$markdown, $data], false);
        Event::fire('markdown.parse', [$markdown, $data], false);

        return $data->text;
    }

    /**
     * Extracts front matter from the Markdown content.
     *
     * The content, minus the front matter, is returned.
     */
    protected function extractFrontMatter(string $markdown): string
    {
        $frontMatterParser = new FrontMatterParser(new SymfonyYamlFrontMatterParser);
        $parts = $frontMatterParser->parse($markdown);
        $this->frontMatterData = $parts->getFrontMatter() ?? [];
        $contents = $parts->getContent();

        return $contents;
    }

    /**
     * Gets the front matter extracted from the document.
     *
     * This should be called after the Markdown has been parsed.
     */
    public function getFrontMatter(): array
    {
        return $this->frontMatterData;
    }

    /**
     * Gets an instance of the Markdown parser within a given environment.
     */
    protected function getParser(Environment $environment): MarkdownParserInterface
    {
        if (isset($this->parserClass)) {
            return new $this->parserClass($environment);
        }

        return new MarkdownParser($environment);
    }

    /**
     * Sets the Markdown parser to use.
     */
    public function setParser(MarkdownParserInterface $parserClass): void
    {
        $this->parserClass = get_class($parserClass);
    }

    /**
     * Gets an instance of the HTML renderer within a given environment.
     */
    protected function getRenderer(Environment $environment): DocumentRendererInterface
    {
        if (isset($this->rendererClass)) {
            return new $this->rendererClass($environment);
        }

        return new HtmlRenderer($environment);
    }

    /**
     * Sets the HTML renderer to use.
     */
    public function setRenderer(DocumentRendererInterface $rendererClass): void
    {
        $this->rendererClass = get_class($rendererClass);
    }

    /**
     * Allows fluent-style enabling and disabling of features.
     */
    public function __call(string $name, array $arguments): static
    {
        $enable = true;

        if (str_starts_with($name, 'set')) {
            $property = lcfirst(str_after($name, 'set'));

            if ($property === 'config') {
                $enable = (array) $arguments[0];
            } else {
                $enable = boolval($arguments[0] ?? true);
            }
        } elseif (str_starts_with($name, 'enable')) {
            $property = lcfirst(str_after($name, 'enable'));
            $enable = true;
        } elseif (str_starts_with($name, 'disable')) {
            $property = lcfirst(str_after($name, 'disable'));
            $enable = false;
        }

        if (!isset($property) || !property_exists(static::class, $property)) {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
        }

        $this->{$property} = $enable;

        return $this;
    }

    /**
     * Allows fluent-style enabling and disabling of features from an initial static call.
     */
    public static function __callStatic(string $name, array $arguments): static
    {
        $enable = true;

        if (str_starts_with($name, 'set')) {
            $property = lcfirst(str_after($name, 'set'));

            if ($property === 'config') {
                $enable = (array) $arguments[0];
            } else {
                $enable = boolval($arguments[0] ?? true);
            }
        } elseif (str_starts_with($name, 'enable')) {
            $property = lcfirst(str_after($name, 'enable'));
            $enable = true;
        } elseif (str_starts_with($name, 'disable')) {
            $property = lcfirst(str_after($name, 'disable'));
            $enable = false;
        }

        if (!isset($property) || !property_exists(static::class, $property)) {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
        }

        $instance = new static;
        $instance->{$property} = $enable;

        return $instance;
    }
}
