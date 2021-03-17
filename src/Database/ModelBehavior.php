<?php namespace Winter\Storm\Database;

use Winter\Storm\Extension\ExtensionBase;

/**
 * Base class for model behaviors.
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class ModelBehavior extends ExtensionBase
{
    /**
     * @var \Winter\Storm\Database\Model Reference to the extended model.
     */
    protected $model;

    /**
     * Constructor
     * @param \Winter\Storm\Database\Model $model The extended model.
     */
    public function __construct($model)
    {
        $this->model = $model;
    }
}
