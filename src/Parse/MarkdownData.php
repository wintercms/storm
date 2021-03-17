<?php namespace Winter\Storm\Parse;

/**
 * Helper class for passing partially parsed Markdown input
 * to and from the markdown.beforeParse and markdown.parse
 * event handlers
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class MarkdownData
{
    /**
     * @var string
     */
    public $text;

    public function __construct($text)
    {
        $this->text = $text;
    }
}
