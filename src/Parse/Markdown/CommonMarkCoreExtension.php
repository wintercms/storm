<?php

declare(strict_types=1);

/*
 * This file is part of the league/commonmark package, modified by Winter CMS to allow indented
 * code blocks to be enabled and disabled.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * Original code based on the CommonMark JS reference parser (https://bitly.com/commonmark-js)
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Winter\Storm\Parse\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Delimiter\Processor\EmphasisDelimiterProcessor;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\CommonMark\Node as CoreNode;
use League\CommonMark\Parser as CoreParser;
use League\CommonMark\Renderer as CoreRenderer;
use League\Config\ConfigurationBuilderInterface;
use Nette\Schema\Expect;

class CommonMarkCoreExtension implements ConfigurableExtensionInterface
{
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $builder->addSchema('commonmark', Expect::structure([
            'use_asterisk' => Expect::bool(true),
            'use_underscore' => Expect::bool(true),
            'enable_strong' => Expect::bool(true),
            'enable_em' => Expect::bool(true),
            'enable_indented_code_blocks' => Expect::bool(true),
            'unordered_list_markers' => Expect::listOf('string')->min(1)->default(['*', '+', '-'])->mergeDefaults(false),
        ]));
    }

    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma,Squiz.WhiteSpace.SemicolonSpacing.Incorrect
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            // Block start parsers
            ->addBlockStartParser(new \League\CommonMark\Extension\CommonMark\Parser\Block\BlockQuoteStartParser(), 70)
            ->addBlockStartParser(new \League\CommonMark\Extension\CommonMark\Parser\Block\HeadingStartParser(), 60)
            ->addBlockStartParser(new \League\CommonMark\Extension\CommonMark\Parser\Block\FencedCodeStartParser(), 50)
            ->addBlockStartParser(new \League\CommonMark\Extension\CommonMark\Parser\Block\HtmlBlockStartParser(), 40)
            ->addBlockStartParser(new \League\CommonMark\Extension\CommonMark\Parser\Block\ThematicBreakStartParser(), 20)
            ->addBlockStartParser(new \League\CommonMark\Extension\CommonMark\Parser\Block\ListBlockStartParser(), 10)

            // Inline parsers
            ->addInlineParser(new CoreParser\Inline\NewlineParser(), 200)
            ->addInlineParser(new \League\CommonMark\Extension\CommonMark\Parser\Inline\BacktickParser(), 150)
            ->addInlineParser(new \League\CommonMark\Extension\CommonMark\Parser\Inline\EscapableParser(), 80)
            ->addInlineParser(new \League\CommonMark\Extension\CommonMark\Parser\Inline\EntityParser(), 70)
            ->addInlineParser(new \League\CommonMark\Extension\CommonMark\Parser\Inline\AutolinkParser(), 50)
            ->addInlineParser(new \League\CommonMark\Extension\CommonMark\Parser\Inline\HtmlInlineParser(), 40)
            ->addInlineParser(new \League\CommonMark\Extension\CommonMark\Parser\Inline\CloseBracketParser(), 30)
            ->addInlineParser(new \League\CommonMark\Extension\CommonMark\Parser\Inline\OpenBracketParser(), 20)
            ->addInlineParser(new \League\CommonMark\Extension\CommonMark\Parser\Inline\BangParser(), 10)

            // Block renderers
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Block\BlockQuoteRenderer(),
                0
            )
            ->addRenderer(
                CoreNode\Block\Document::class,
                new CoreRenderer\Block\DocumentRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Block\FencedCode::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Block\Heading::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Block\HeadingRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Block\HtmlBlockRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Block\ListBlock::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Block\ListBlockRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Block\ListItem::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Block\ListItemRenderer(),
                0
            )
            ->addRenderer(
                CoreNode\Block\Paragraph::class,
                new CoreRenderer\Block\ParagraphRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Block\ThematicBreakRenderer(),
                0
            )

            // Inline renderers
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Inline\Code::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Inline\CodeRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Inline\EmphasisRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Inline\HtmlInlineRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Inline\Image::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Inline\ImageRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Inline\Link::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Inline\LinkRenderer(),
                0
            )
            ->addRenderer(
                CoreNode\Inline\Newline::class,
                new CoreRenderer\Inline\NewlineRenderer(),
                0
            )
            ->addRenderer(
                \League\CommonMark\Extension\CommonMark\Node\Inline\Strong::class,
                new \League\CommonMark\Extension\CommonMark\Renderer\Inline\StrongRenderer(),
                0
            )
            ->addRenderer(
                CoreNode\Inline\Text::class,
                new CoreRenderer\Inline\TextRenderer(),
                0
            )
        ;

        if ($environment->getConfiguration()->get('commonmark/enable_indented_code_blocks')) {
            $environment
                ->addBlockStartParser(new \League\CommonMark\Extension\CommonMark\Parser\Block\IndentedCodeStartParser(), -100)
                ->addRenderer(
                    \League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode::class,
                    new \League\CommonMark\Extension\CommonMark\Renderer\Block\IndentedCodeRenderer(),
                    0
                );
        }

        if ($environment->getConfiguration()->get('commonmark/use_asterisk')) {
            $environment->addDelimiterProcessor(new EmphasisDelimiterProcessor('*'));
        }

        if ($environment->getConfiguration()->get('commonmark/use_underscore')) {
            $environment->addDelimiterProcessor(new EmphasisDelimiterProcessor('_'));
        }
    }
}
