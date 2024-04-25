<?php

namespace Winter\Storm\Database\Concerns;

/**
 * Hides and shows attributes for serialization.
 */
trait HidesAttributes
{
    /**
     * Add hidden attributes for the model.
     *
     * This restores the `addHidden` method that was removed from Laravel 7 onwards. It is however recommended to use
     * the `makeHidden` method going forward.
     *
     * @param array|string|null $attributes
     */
    public function addHidden($attributes = null): void
    {
        $this->hidden = array_merge(
            $this->hidden,
            is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * Add visible attributes for the model.
     *
     * This restores the `addVisible` method that was removed from Laravel 7 onwards. It is however recommended to use
     * the `makeVisible` method going forward.
     *
     * @param array|string|null $attributes
     */
    public function addVisible($attributes = null): void
    {
        $this->visible = array_merge(
            $this->visible,
            is_array($attributes) ? $attributes : func_get_args()
        );
    }
}
