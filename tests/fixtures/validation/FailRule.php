<?php

use Winter\Storm\Validation\Rule;

class FailRule extends Rule
{
    public function passes($attribute, $value)
    {
        return false;
    }

    public function message()
    {
        return 'Fallback message';
    }
}
