<?php
namespace ManaPHP\Http;

interface FilterInterface
{

    /**
     * @param string   $name
     * @param callable $method
     *
     * @return static
     */
    public function addRule($name, $method);

    /**
     * @param array $attributes
     *
     * @return static
     */
    public function addAttributes($attributes);

    /**
     * @param string $attribute
     * @param string $rules
     * @param string $value
     *
     * @return mixed
     */
    public function sanitize($attribute, $rules, $value);
}