<?php
namespace ManaPHP;

use ManaPHP\Facade\Exception as FacadeException;

/**
 * Class ManaPHP\Facade
 *
 * @package facade
 */
class Facade
{
    /**
     * @var array
     */
    protected static $_instances = [];

    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeInstance()
    {
        $className = get_called_class();

        if (!isset(static::$_instances[$className])) {
            static::$_instances[$className] = Di::getDefault()->getShared(static::getFacadeName());
        }

        return static::$_instances[$className];
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeName()
    {
        $className = get_called_class();
        return lcfirst(substr($className, strrpos($className, '\\') + 1));
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string $method
     * @param  array  $arguments
     *
     * @return mixed
     *
     * @throws \ManaPHP\Facade\Exception
     */
    public static function __callStatic($method, $arguments)
    {
        $instance = static::getFacadeInstance();
        if (!$instance) {
            throw new FacadeException('');
        }

        switch (count($arguments)) {
            case 0:
                return $instance->$method();
            case 1:
                return $instance->$method($arguments[0]);
            case 2:
                return $instance->$method($arguments[0], $arguments[1]);
            case 3:
                return $instance->$method($arguments[0], $arguments[1], $arguments[2]);
            default:
                return call_user_func_array([$instance, $method], $arguments);
        }
    }
}