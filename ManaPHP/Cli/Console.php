<?php
namespace ManaPHP\Cli;

use ManaPHP\Component;

/**
 * Class ManaPHP\Cli\Console
 *
 * @package ManaPHP\Cli
 */
class Console extends Component implements ConsoleInterface
{
    /**
     * @param string $str
     * @param array  $context
     *
     * @return static
     */
    public function write($str, $context = [])
    {
        if (count($context) === 0) {
            echo $str;
        } else {
            $replaces = [];

            foreach ($context as $k => $v) {
                $replaces[':' . $k] = $v;
            }

            echo strtr($str, $replaces);
        }

        return $this;
    }

    /**
     * @param string $str
     * @param array  $context
     *
     * @return static
     */
    public function writeLn($str, $context = [])
    {
        return $this->write($str . PHP_EOL, $context);
    }

    /**
     * @param string $str
     * @param array  $context
     * @param int    $code
     *
     * @return int
     */
    public function error($str, $context = [], $code = 1)
    {
        $this->writeLn($str, $context);

        return $code;
    }
}