<?php

use Winter\Storm\Validation\Rule;

class TranslatedFailRule extends Rule
{
    public function passes($attribute, $value)
    {
        return false;
    }

    public function message()
    {
        return 'lang.validation.fail';
    }
}
