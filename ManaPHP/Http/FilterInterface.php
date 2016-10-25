<?php
namespace ManaPHP\Http;

/**
 * Interface ManaPHP\Http\FilterInterface
 *
 * @package filter
 */
interface FilterInterface
{
    /**
     * @param string   $name
     * @param callable $method
     *
     * @return static
     */
    public function addFilter($name, $method);

    /**
     * @param string $attribute
     * @param string $rule
     * @param string $name
     *
     * @return static
     */
    public function addRule($attribute, $rule, $name = null);

    /**
     * @param string $attribute
     * @param string $rule
     * @param string $value
     *
     * @return mixed
     */
    public function sanitize($attribute, $rule, $value);
}