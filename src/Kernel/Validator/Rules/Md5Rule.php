<?php


namespace App\Kernel\Validator\Rules;


use Hyperf\Validation\Validator;

class Md5Rule implements RuleInterface
{

    const NAME = 'md5';
    /**
     * @inheritDoc
     */
    public function passes($attribute, $value, $parameters, Validator $validator): bool
    {
        return preg_match("/^[a-z0-9]{32}$/", $value);
    }

    /**
     * @inheritDoc
     */
    public function message($message, $attribute, $rule, $parameters, Validator $validator): string
    {
        return $attribute . '必须是md5';
    }
}