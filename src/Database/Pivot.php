<?php namespace Winter\Storm\Database;

use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class Pivot extends Model
{
    use AsPivot;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */
    protected $guarded = [];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Gets the parent attribute.
     *
     * Provided for backwards-compatibility.
     *
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getParentAttribute($value)
    {
        return $this->pivotParent;
    }

    /**
     * Sets the parent attribute.
     *
     * Provided for backwards-compatibility.
     *
     * @param \Illuminate\Database\Eloquent\Model $value
     * @return void
     */
    public function setParentAttribute($value)
    {
        $this->pivotParent = $value;
    }
}
