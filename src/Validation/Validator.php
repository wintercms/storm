<?php namespace Winter\Storm\Validation;

use Illuminate\Validation\Validator as BaseValidator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

/**
 * Winter CMS wrapper for the Laravel Validator class.
 *
 * The only difference between this and the BaseValidator is that it resets the email validation rule to use the
 * `filter` method by default.
 *
 * It also does some custom handling of Rule objects through the FormatsMessages concern.
 */
class Validator extends BaseValidator implements ValidatorContract
{
    use Concerns\ValidatesEmail;
    use Concerns\FormatsMessages;

    /**
     * Validate an attribute using a custom rule object.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Illuminate\Contracts\Validation\Rule  $rule
     * @return void
     */
    protected function validateUsingCustomRule($attribute, $value, $rule)
    {
        if (!$rule->passes($attribute, $value)) {
            $this->failedRules[$attribute][get_class($rule)] = [];

            $messages = $rule->message() ? (array) $rule->message() : [get_class($rule)];

            foreach ($messages as $message) {
                $this->messages->add($attribute, $this->makeReplacements(
                    $this->getTranslator()->get($message),
                    $attribute,
                    get_class($rule),
                    []
                ));
            }
        }
    }
}
