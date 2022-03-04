<?php namespace Winter\Storm\Parse\PHP;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
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
        if ($this->hasNodeWithComments($nodes) || (isset($nodes[0]) && $nodes[0] instanceof Expr\ArrayItem)) {
            return $this->pCommaSeparatedMultiline($nodes, $trailingComma) . $this->nl;
        } else {
            return $this->pCommaSeparated($nodes);
        }
    }

    /**
     * Pretty prints a comma-separated list of nodes in multiline style, including comments.
     *
     * The result includes a leading newline and one level of indentation (same as pStmts).
     *
     * @param Node[] $nodes         Array of Nodes to be printed
     * @param bool   $trailingComma Whether to use a trailing comma
     *
     * @return string Comma separated pretty printed nodes in multiline style
     */
    protected function pCommaSeparatedMultiline(array $nodes, bool $trailingComma) : string {
        $this->indent();

        $result = '';
        $lastIdx = count($nodes) - 1;
        foreach ($nodes as $idx => $node) {
            if ($node !== null) {
                $comments = $node->getComments();
                if ($comments) {
                    $result .= $this->pComments($comments);
                }

                $result .= $this->nl . $this->p($node);
            } else {
                $result = trim($result) . "\n";
            }
            if ($trailingComma || $idx !== $lastIdx) {
                $result .= ',';
            }
        }

        $this->outdent();
        return $result;
    }

    /**
     * Prints reformatted text of the passed comments.
     *
     * @param Comment[] $comments List of comments
     *
     * @return string Reformatted text of comments
     */
    protected function pComments(array $comments): string
    {
        $formattedComments = [];

        foreach ($comments as $comment) {
            $formattedComments[] = str_replace("\n", $this->nl, $comment->getReformattedText());
        }

        $padding = $comments[0]->getStartLine() !== $comments[count($comments) - 1]->getEndLine() ? $this->nl : '';

        return "\n" . $this->nl . trim($padding . implode($this->nl, $formattedComments)) . "\n";
    }

    protected function pExpr_Include(Expr\Include_ $node)
    {
        static $map = [
            Expr\Include_::TYPE_INCLUDE      => 'include',
            Expr\Include_::TYPE_INCLUDE_ONCE => 'include_once',
            Expr\Include_::TYPE_REQUIRE      => 'require',
            Expr\Include_::TYPE_REQUIRE_ONCE => 'require_once',
        ];

        return $map[$node->type] . '(' . $this->p($node->expr) . ')';
    }
}
