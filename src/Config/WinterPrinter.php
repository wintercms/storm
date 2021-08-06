<?php namespace Winter\Storm\Config;

use PhpParser\Node\Expr\ArrayItem;
use PhpParser\PrettyPrinter\Standard;

/**
 * Class WinterPrinter
 * @package Winter\Storm\Config
 */
class WinterPrinter extends Standard
{
    /**
     * @param array $nodes
     * @param bool $trailingComma
     * @return string
     */
    protected function pMaybeMultiline(array $nodes, bool $trailingComma = false)
    {
        if ($this->hasNodeWithComments($nodes) || (isset($nodes[0]) && $nodes[0] instanceof ArrayItem)) {
            return $this->pCommaSeparatedMultiline($nodes, $trailingComma) . $this->nl;
        } else {
            return $this->pCommaSeparated($nodes);
        }
    }

    /**
     * @param array $comments
     * @return string
     */
    protected function pComments(array $comments): string
    {
        $formattedComments = [];

        foreach ($comments as $comment) {
            $formattedComments[] = str_replace("\n", $this->nl, $comment->getReformattedText());
        }

        $padding = $comments[0]->getStartLine() !== $comments[count($comments) - 1]->getEndLine() ? $this->nl : '';

        return $padding . implode($this->nl, $formattedComments) . $padding;
    }
}
