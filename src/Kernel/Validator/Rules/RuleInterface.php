<?php

namespace App\Kernel\Validator\Rules;
use Hyperf\Validation\Validator;

interface RuleInterface
{
    const PASSES_NAME = 'passes';
    const MESSAGE_NAME = 'message';

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @param Validator $validator
     * @return bool
     */
    public function passes($attribute, $value, $parameters, Validator $validator): bool;

    /**
     *
     * @param $message
     * @param $attribute
     * @param $rule
     * @param $parameters
     * @param Validator $validator
     * @return string
     */
    public function message($message, $attribute, $rule, $parameters, Validator $validator): string;
}