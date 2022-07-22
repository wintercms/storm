<?php namespace Winter\Storm\Halcyon;

/**
 * The Model interface defines required methods for models to work.
 *
 * At the moment, this only restricts the signature for the constructor to only allow attributes as a parameter, in
 * order to allow several static calls to work within the model architecture, but still allow models to extend the
 * constructor if they wish.
 *
 * @author Winter CMS
 */
interface ModelInterface
{
    /**
     * Create a new model instance.
     *
     * @param array $attributes A list of attributes to populate in the model.
     */
    public function __construct(array $attributes = []);
}
