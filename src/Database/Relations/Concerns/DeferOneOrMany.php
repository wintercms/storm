<?php namespace Winter\Storm\Database\Relations\Concerns;

use Illuminate\Database\Query\Grammars\Grammar;
use Winter\Storm\Support\Facades\DbDongle;
use Winter\Storm\Database\Relations\BelongsToMany;
use Winter\Storm\Database\Relations\MorphToMany;

trait DeferOneOrMany
{
    /**
     * Returns the model query with deferred bindings added
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function withDeferred($sessionKey)
    {
        $modelQuery = $this->query;

        $newQuery = $modelQuery->getQuery()->newQuery();

        $newQuery->from($this->related->getTable());

        /*
         * No join table will be used, strip the selected "pivot_" columns
         */
        /** @phpstan-ignore-next-line */
        if ($this instanceof BelongsToMany || $this instanceof MorphToMany) {
            $this->orphanMode = true;
        }

        $newQuery->where(function ($query) use ($sessionKey) {

            if ($this->parent->exists) {
                /** @phpstan-ignore-next-line */
                if ($this instanceof MorphToMany) {
                    /*
                     * Custom query for MorphToMany since a "join" cannot be used
                     */
                    $query->whereExists(function ($query) {
                        $query
                            ->select($this->parent->getConnection()->raw(1))
                            ->from($this->table)
                            ->where($this->getOtherKey(), DbDongle::raw(
                                DbDongle::getTablePrefix() . $this->related->getQualifiedKeyName()
                            ))
                            ->where($this->getForeignKey(), $this->parent->getKey())
                            ->where($this->getMorphType(), $this->getMorphClass());
                    });
                /** @phpstan-ignore-next-line */
                } elseif ($this instanceof BelongsToMany) {
                    /*
                     * Custom query for BelongsToMany since a "join" cannot be used
                     */
                    $query->whereExists(function ($query) {
                        $query
                            ->select($this->parent->getConnection()->raw(1))
                            ->from($this->table)
                            ->where($this->getOtherKey(), DbDongle::raw(
                                DbDongle::getTablePrefix() . $this->related->getQualifiedKeyName()
                            ))
                            ->where($this->getForeignKey(), $this->parent->getKey());
                    });
                } else {
                    /*
                     * Trick the relation to add constraints to this nested query
                     */
                    $this->query = $query;
                    $this->addConstraints();
                }

                $this->addDefinedConstraintsToQuery($this);
            }

            /*
             * Bind (Add)
             */
            $query = $query->orWhereIn($this->getWithDeferredQualifiedKeyName(), function ($query) use ($sessionKey) {
                $query
                    ->select('slave_id')
                    ->from('deferred_bindings')
                    ->where('master_field', $this->relationName)
                    ->where('master_type', get_class($this->parent))
                    ->where('session_key', $sessionKey)
                    ->where('is_bind', 1);
            });
        });

        /*
         * Unbind (Remove)
         */
        $newQuery->whereNotIn($this->getWithDeferredQualifiedKeyName(), function ($query) use ($sessionKey) {
            $query
                ->select('slave_id')
                ->from('deferred_bindings')
                ->where('master_field', $this->relationName)
                ->where('master_type', get_class($this->parent))
                ->where('session_key', $sessionKey)
                ->where('is_bind', 0)
                ->whereRaw(DbDongle::parse('id > ifnull((select max(id) from '.DbDongle::getTablePrefix().'deferred_bindings where
                        slave_id = '.$this->getWithDeferredQualifiedKeyName()->getValue(new Grammar).' and
                        master_field = ? and
                        master_type = ? and
                        session_key = ? and
                        is_bind = ?
                    ), 0)'), [
                    $this->relationName,
                    get_class($this->parent),
                    $sessionKey,
                    1
                ]);
        });

        $modelQuery->setQuery($newQuery);

        /*
         * Apply global scopes
         */
        foreach ($this->related->getGlobalScopes() as $identifier => $scope) {
            $modelQuery->withGlobalScope($identifier, $scope);
        }

        return $this->query = $modelQuery;
    }

    /**
     * Returns the related "slave id" key in a database friendly format.
     * @return \Illuminate\Contracts\Database\Query\Expression
     */
    protected function getWithDeferredQualifiedKeyName()
    {
        return $this->parent->getConnection()->raw(DbDongle::cast(
            DbDongle::getTablePrefix() . $this->related->getQualifiedKeyName(),
            'TEXT'
        ));
    }
}
