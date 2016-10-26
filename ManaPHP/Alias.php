<?php
namespace ManaPHP;

use ManaPHP\Alias\Exception as AliasException;

/**
 * Class ManaPHP\Alias
 *
 * @package alias
 */
class Alias extends Component implements AliasInterface
{
    /**
     * @var array
     */
    protected $_aliases = [];

    /**
     * Alias constructor.
     *
     * @throws \ManaPHP\Alias\Exception
     */
    public function __construct()
    {
        $this->set('@manaphp', str_replace('\\', '/', __DIR__));
    }

    /**
     * @param string $name
     * @param string $path
     *
     * @return string
     * @throws \ManaPHP\Alias\Exception
     */
    public function set($name, $path)
    {
        if ($name[0] !== '@') {
            throw new AliasException('`:name` must start with `@`'/**m02b52e71dba71561a*/, ['name' => $name]);
        }

        if (strpos($name, '@ns.') === 0) {
            $this->_aliases[$name] = $path[0] === '@' ? $this->resolve($path) : $path;
        } else {
            $this->_aliases[$name] = $this->resolve($path);
        }

        return $this->_aliases[$name];
    }

    /**
     * @param string $name
     *
     * @return bool|string
     * @throws \ManaPHP\Alias\Exception
     */
    public function get($name)
    {
        if ($name[0] !== '@') {
            throw new AliasException('`:name` must start with `@`'/**m0f809631289d02f8e*/, ['name' => $name]);
        }

        return isset($this->_aliases[$name]) ? $this->_aliases[$name] : false;
    }

    /**
     * @param string $name
     *
     * @return bool
     * @throws \ManaPHP\Alias\Exception
     */
    public function has($name)
    {
        if ($name[0] !== '@') {
            throw new AliasException('`:name` must start with `@`'/**m0f7f21386c79f1518*/, ['name' => $name]);
        }

        return isset($this->_aliases[$name]);
    }

    /**
     * @param string $path
     *
     * @return string
     * @throws \ManaPHP\Alias\Exception
     */
    public function resolve($path)
    {
        if (rtrim($path, '\\/') !== $path) {

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new AliasException('`:path` can not end with `/` or `\`'/**m02677305f62c5336e*/, ['path' => $path]);
        }

        if ($path[0] !== '@') {
            return str_replace('\\', '/', $path);
        }

        if (strpos($path, '@ns.') === 0) {
            $parts = explode('\\', $path, 2);
        } else {
            $path = str_replace('\\', '/', $path);
            $parts = explode('/', $path, 2);
        }

        $alias = $parts[0];
        if (!isset($this->_aliases[$alias])) {

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new AliasException('`:alias` is not exists for `:path`'/**m0aac421937afe5850*/, ['alias' => $alias, 'path' => $path]);
        }

        return str_replace($alias, $this->_aliases[$alias], $path);
    }
}