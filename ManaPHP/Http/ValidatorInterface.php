<?php
namespace ManaPHP\Http;

/**
 * Interface ManaPHP\Http\ValidatorInterface
 *
 * @package validator
 */
interface ValidatorInterface
{
    /**
     * @param string $attribute
     * @param string $rule
     * @param string $value
     *
     * @return mixed
     */
    public function validate($attribute, $rule, $value);
}