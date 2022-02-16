<?php namespace Winter\Storm\Parse\PHP;

use PhpParser\Node\Expr\ArrayItem;
use PhpParser\PrettyPrinter\Standard;

class ArrayPrinter extends Standard
{
    public function __construct(array $options = [])
    {
        if (!isset($options['shortArraySyntax'])) {
            $options['shortArraySyntax'] = true;
        }

        parent::__construct($options);
    }

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
