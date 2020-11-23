<?php


namespace App\Kernel\Validator\Rules;


use Hyperf\Validation\Validator;

class MobileRule implements RuleInterface
{
    const NAME = 'mobile';

    /**
     * @inheritDoc
     */
    public function passes($attribute, $value, $parameters, Validator $validator): bool
    {
        return preg_match('/^1\d{10}$/', $value);
    }

    /**
     * @inheritDoc
     */
    public function message($message, $attribute, $rule, $parameters, Validator $validator): string
    {
        return $attribute . '必须是11位手机号';
    }
}