<?php

namespace Winter\Storm\Database\Concerns;

trait HasAttributes
{
    /**
     * Get the attributes that should be converted to dates.
     *
     * This reimplements the $dates property that was dropped from Laravel 10. However, it is
     * recommended that future code uses $casts instead.
     *
     * ```
     * protected $casts = [
     *    'deployed_at' => 'datetime',
     * ];
     * ```
     *
     * @return array
     */
    public function getDates()
    {
        if (! $this->usesTimestamps()) {
            return $this->dates;
        }

        $defaults = [
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        return array_unique(array_merge($this->dates, $defaults));
    }
}
