<?php namespace Winter\Storm\Parse\PHP;

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

        return $padding . implode($this->nl, $formattedComments) . $padding;
    }

    protected function pExpr_Include(Expr\Include_ $node)
    {
        static $map = [
            Expr\Include_::TYPE_INCLUDE      => 'include',
            Expr\Include_::TYPE_INCLUDE_ONCE => 'include_once',
            Expr\Include_::TYPE_REQUIRE      => 'require',
            Expr\Include_::TYPE_REQUIRE_ONCE => 'require_once',
        ];

        $includeInConfigArray = false;

        if ($map[$node->type] === 'include') {
            foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 50) as $backtrace) {
                if (
                    $backtrace['function'] !== 'pStmts'
                    || !isset($backtrace['args'][0])
                    || !is_array($backtrace['args'][0])
                ) {
                    continue;
                }

                foreach ($backtrace['args'][0] as $arg) {
                    if (!($arg instanceof Stmt\Return_)) {
                        continue;
                    }

                    $includeInConfigArray = ($iterator = function ($arg) use (&$iterator, $node) {
                        if ($arg instanceof Expr\Array_) {
                            foreach ($arg->items as $item) {
                                if ($iterator($item)) {
                                    return true;
                                }
                            }
                        }
                        if ($arg instanceof Expr\ArrayItem) {
                            if ($arg->value instanceof Expr\FuncCall) {
                                foreach ($arg->value->args as $funcArg) {
                                    if ($iterator($funcArg->value)) {
                                        return true;
                                    }
                                }
                            }
                        }
                        if ($arg instanceof Expr\Include_ && $node === $arg) {
                            return true;
                        }
                        return false;
                    })($arg->expr);
                }
            }
        }

        return $includeInConfigArray
            ? $map[$node->type] . '(' . $this->p($node->expr) . ')'
            : $map[$node->type] . ' ' . $this->p($node->expr);
    }
}
