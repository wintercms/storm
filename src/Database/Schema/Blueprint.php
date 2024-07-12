<?php namespace Winter\Storm\Database\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Winter\Storm\Support\Facades\Schema;

/**
 * Proxy class
 */
class Blueprint extends BaseBlueprint
{
    /**
     * Indicate that the given columns should be dropped if it exists.
     *
     * @param  array|mixed  $columns
     * @return \Illuminate\Support\Fluent
     */
    public function dropColumnIfExists($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $columns = collect($columns)->filter(function($column) {
            return Schema::hasColumn($this->getTable(), $column);
        })->values()->all();

        return !empty($columns) ? $this->dropColumn($columns) : $this;
    }
}
