<?php namespace Winter\Storm\Html;

use Exception;

/**
 * Block manager
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class BlockBuilder
{
    /**
     * The block stack.
     */
    protected array $blockStack = [];

    /**
     * Registered block contents, keyed by block name.
     */
    protected array $blocks = [];

    /**
     * Helper method for the "startBlock" templating function.
     */
    public function put(string $name): void
    {
        $this->startBlock($name);
    }

    /**
     * Begins the layout block for a given block name.
     *
     * This method enables output buffering, so all output will be captured as a part of this block.
     */
    public function startBlock(string $name): void
    {
        array_push($this->blockStack, $name);
        ob_start();
    }

    /**
     * Helper method for the "endBlock" templating function.
     *
     * If `$append` is `true`, the new content should be appended to an existing block, as opposed to overwriting any
     * previous content.
     *
     * @throws \Exception if there are no items in the block stack
     */
    public function endPut(bool $append = false): void
    {
        $this->endBlock($append);
    }

    /**
     * Closes the layout block.
     *
     * This captures all buffered output as the block's content, and ends output buffering.
     *
     * @throws \Exception if there are no items in the block stack
     */
    public function endBlock(bool $append = false): void
    {
        if (!count($this->blockStack)) {
            throw new Exception('Invalid block nesting');
        }

        $name = array_pop($this->blockStack);
        $contents = ob_get_clean();

        if ($append) {
            $this->append($name, $contents);
        } else {
            $this->blocks[$name] = $contents;
        }
    }

    /**
     * Sets a content of the layout block, overwriting any previous content for that block.
     *
     * Output buffering is not used for this method.
     */
    public function set(string $name, string $content): void
    {
        $this->blocks[$name] = $content;
    }

    /**
     * Appends content to a layout block.
     *
     * Output buffering is not used for this method.
     */
    public function append(string $name, string $content): void
    {
        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = '';
        }

        $this->blocks[$name] .= $content;
    }

    /**
     * Returns the layout block contents of a given block name and deletes the block from memory.
     *
     * If the block does not exist, then the `$default` content will be returned instead.
     */
    public function placeholder(string $name, string $default = null): ?string
    {
        $result = $this->get($name, $default);
        unset($this->blocks[$name]);

        if (is_string($result)) {
            $result = trim($result);
        }

        return $result;
    }

    /**
     * Returns the layout block contents of a given name, but does not delete it from memory.
     *
     * If the block does not exist, then the `$default` content will be returned instead.
     */
    public function get(string $name, string $default = null): ?string
    {
        if (!isset($this->blocks[$name])) {
            return $default;
        }

        return $this->blocks[$name];
    }

    /**
     * Clears all the registered blocks.
     */
    public function reset(): void
    {
        $this->blockStack = [];
        $this->blocks = [];
    }

    /**
     * Gets the block stack at this point.
     */
    public function getBlockStack(): array
    {
        return $this->blockStack;
    }
}
