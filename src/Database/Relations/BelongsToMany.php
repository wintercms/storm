<?php namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;

class BelongsToMany extends BelongsToManyBase
{
    use Concerns\BelongsOrMorphsToMany;
    use Concerns\DeferOneOrMany;
    use Concerns\DefinedConstraints;
}
