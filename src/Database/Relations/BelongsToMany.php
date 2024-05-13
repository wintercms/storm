<?php namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;

class BelongsToMany extends BelongsToManyBase
{
    use Concerns\BelongsOrMorphToMany;
    use Concerns\DeferOneOrMany;
    use Concerns\DefinedConstraints;
}
