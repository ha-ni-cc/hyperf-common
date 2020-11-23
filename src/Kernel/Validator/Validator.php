<?php


namespace App\Kernel\Validator;

use App\Exception\ParamException;
use App\Kernel\Validator\Rules\Md5Rule;
use App\Kernel\Validator\Rules\MobileRule;
use App\Kernel\Validator\Rules\RuleInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

class Validator
{
    protected static $extends = [];

    public static function getValidator(): ValidatorFactoryInterface
    {
        static $validator = null;
        if (is_null($validator)) {
            $container = ApplicationContext::getContainer();
            $validator = $container->get(ValidatorFactoryInterface::class);
            self::initExtends();
            self::registerExtends($validator, self::$extends);
        }

        return $validator;
    }

    protected static function initExtends()
    {
        // 更多自定义的扩展
        self::$extends = [
            MobileRule::NAME => new MobileRule,
            Md5Rule::NAME => new Md5Rule,
        ];
    }

    protected static function registerExtends(ValidatorFactoryInterface $validator, array $extends)
    {
        foreach ($extends as $key => $extend) {
            if ($extend instanceof RuleInterface) {
                $validator->extend($key, function (...$args) use ($extend) {
                    return call_user_func_array([$extend, RuleInterface::PASSES_NAME], $args);
                });
                $validator->replacer($key, function (...$args) use ($extend) {
                    return call_user_func_array([$extend, RuleInterface::MESSAGE_NAME], $args);
                });
            }
        }
    }

    /**
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param bool $firstError
     * @return bool
     * @throws ParamException
     */
    public static function make(array &$data, array $rules, array $messages = [], bool $firstError = true): bool
    {
        $validator = self::getValidator();
        if (empty($messages)) {
            $messages = self::messages();
        }
        $valid = $validator->make($data, $rules, $messages);
        if ($valid->fails()) {
            $errors = $valid->errors();
            $error = $firstError ? $errors->first() : $errors;
            throw new ParamException($error);
        }
        $data = $valid->validated();
        return true;
    }

    public static function messages(): array
    {
        return [];
    }
}