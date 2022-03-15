<?php namespace Winter\Storm\Parse\PHP;

use PhpParser\Internal\DiffElem;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

class ArrayPrinter extends Standard
{
    /**
     * @var Lexer|null Lexer for use by `PhpParser`
     */
    protected $lexer = null;

    /**
     * Creates a pretty printer instance using the given options.
     *
     * Supported options:
     *  * bool $shortArraySyntax = false: Whether to use [] instead of array() as the default array
     *                                    syntax, if the node does not specify a format.
     *
     * @param array $options Dictionary of formatting options
     */
    public function __construct(array $options = [])
    {
        if (!isset($options['shortArraySyntax'])) {
            $options['shortArraySyntax'] = true;
        }

        parent::__construct($options);
    }

    /**
     * Proxy of `prettyPrintFile` to allow for adding lexer token checking support during render.
     * Pretty prints a file of statements (includes the opening <?php tag if it is required).
     *
     * @param Node[] $stmts Array of statements
     *
     * @return string Pretty printed statements
     */
    public function render(array $stmts, Lexer $lexer): string
    {
        if (!$stmts) {
            return "<?php\n\n";
        }

        $this->lexer = $lexer;

        $p = "<?php\n\n" . $this->prettyPrint($stmts);

        if ($stmts[0] instanceof Stmt\InlineHTML) {
            $p = preg_replace('/^<\?php\s+\?>\n?/', '', $p);
        }
        if ($stmts[count($stmts) - 1] instanceof Stmt\InlineHTML) {
            $p = preg_replace('/<\?php$/', '', rtrim($p));
        }

        $this->lexer = null;

        return $p;
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
    protected function pCommaSeparatedMultiline(array $nodes, bool $trailingComma): string
    {
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
     * Render an array expression
     *
     * @param Expr\Array_ $node Array expression node
     *
     * @return string Comma separated pretty printed nodes in multiline style
     */
    protected function pExpr_Array(Expr\Array_ $node): string
    {
        $default = $this->options['shortArraySyntax']
            ? Expr\Array_::KIND_SHORT
            : Expr\Array_::KIND_LONG;

        $ops = $node->getAttribute('kind', $default) === Expr\Array_::KIND_SHORT
            ? ['[', ']']
            : ['array(', ')'];

        if (!count($node->items) && $comments = $this->getNodeComments($node)) {
            // the array has no items, we can inject whatever we want
            return sprintf(
                '%s%s%s%s%s',
                // opening control char
                $ops[0],
                // indent and add nl string
                $this->indent(),
                // join all comments with nl string
                implode($this->nl, $comments),
                // outdent and add nl string
                $this->outdent(),
                // closing control char
                $ops[1]
            );
        }

        if ($comments = $this->getCommentsNotInArray($node)) {
            // array has items, we have detected comments not included within the array, therefore we have found
            // trailing comments and must append them to the end of the array
            return sprintf(
                '%s%s%s%s%s%s',
                // opening control char
                $ops[0],
                // render the children
                $this->pMaybeMultiline($node->items, true),
                // add 1 level of indentation
                str_repeat(' ', 4),
                // join all comments with the current indentation
                implode($this->nl . str_repeat(' ', 4), $comments),
                // add a trailing nl
                $this->nl,
                // closing control char
                $ops[1]
            );
        }

        // default return
        return $ops[0] . $this->pMaybeMultiline($node->items, true) . $ops[1];
    }

    /**
     * Increase indentation level.
     * Proxied to allow for nl return
     *
     * @return string
     */
    protected function indent(): string
    {
        $this->indentLevel += 4;
        $this->nl .= '    ';
        return $this->nl;
    }

    /**
     * Decrease indentation level.
     * Proxied to allow for nl return
     *
     * @return string
     */
    protected function outdent(): string
    {
        assert($this->indentLevel >= 4);
        $this->indentLevel -= 4;
        $this->nl = "\n" . str_repeat(' ', $this->indentLevel);
        return $this->nl;
    }

    /**
     * Get all comments that have not been attributed to a node within a node array
     *
     * @param Expr\Array_ $nodes Array of nodes
     *
     * @return array Comments found
     */
    protected function getCommentsNotInArray(Expr\Array_ $nodes): array
    {
        if (!$comments = $this->getNodeComments($nodes)) {
            return [];
        }

        return array_filter($comments, function ($comment) use ($nodes) {
            return !$this->commentInNodeList($nodes->items, $comment);
        });
    }

    /**
     * Recursively check if a comment exists in an array of nodes
     *
     * @param Node[] $nodes Array of nodes
     * @param string $comment The comment to search for
     *
     * @return bool
     */
    protected function commentInNodeList(array $nodes, string $comment): bool
    {
        foreach ($nodes as $node) {
            if ($node->value instanceof Expr\Array_ && $this->commentInNodeList($node->value->items, $comment)) {
                return true;
            }
            if ($nodeComments = $node->getAttribute('comments')) {
                foreach ($nodeComments as $nodeComment) {
                    if ($nodeComment->getText() === $comment) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check the lexer tokens for comments within the node's start & end position
     *
     * @param Node $node Node to check
     *
     * @return ?array
     */
    protected function getNodeComments(Node $node): ?array
    {
        $tokens = $this->lexer->getTokens();
        $pos = $node->getAttribute('startTokenPos');
        $end = $node->getAttribute('endTokenPos');
        $endLine = $node->getAttribute('endLine');
        $content = [];

        while (++$pos < $end) {
            if (!isset($tokens[$pos]) || !is_array($tokens[$pos]) || $tokens[$pos][0] === T_WHITESPACE) {
                continue;
            }

            list($type, $string, $line) = $tokens[$pos];

            if ($line > $endLine) {
                break;
            }

            if ($type === T_COMMENT || $type === T_DOC_COMMENT) {
                $content[] = $string;
            } elseif ($content) {
                return $content;
            }
        }

        return empty($content) ? null : $content;
    }

    /**
     * Prints reformatted text of the passed comments.
     *
     * @param array $comments List of comments
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
