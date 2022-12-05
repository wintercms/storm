<?php namespace Winter\Storm\Validation\Rules;

use Winter\Storm\Support\Str;
use Winter\Storm\Validation\Rule;

class Slug extends Rule
{
    /**
     * The separator to be used when generating the slug
     */
    protected string $separator = '-';

    /**
     * {@inheritDoc}
     */
    public function __construct(string $separator = '-')
    {
        $this->separator = $separator;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return Str::slug($value, $this->separator) === $value;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return "The :attribute must contain only alphanumeric characters and the separator ({$this->separator}).";
    }
}
