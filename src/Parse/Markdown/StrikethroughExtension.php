<?php

declare(strict_types=1);

/*
 * This file is part of the league/commonmark package, modified by Winter CMS to keep strikethrough
 * only valid with 2 tildes.
 *
 * (c) Colin O'Dell <colinodell@gmail.com> and uAfrica.com (http://uafrica.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Winter\Storm\Parse\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\Strikethrough\Strikethrough;
use League\CommonMark\Extension\Strikethrough\StrikethroughRenderer;

final class StrikethroughExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addDelimiterProcessor(new StrikethroughDelimiterProcessor());
        $environment->addRenderer(Strikethrough::class, new StrikethroughRenderer());
    }
}
