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
     * @param string|array $str
     * @param array        $context
     *
     * @return static
     */
    public function write($str, $context = [])
    {
        if (is_array($str)) {
            $str = json_encode($str, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

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
     * @param string|array $str
     * @param array        $context
     *
     * @return static
     */
    public function writeLn($str = '', $context = [])
    {
        $this->write($str, $context);
        $this->write(PHP_EOL);

        return $this;
    }

    /**
     * @param string|array $str
     * @param array        $context
     * @param int          $code
     *
     * @return int
     */
    public function error($str, $context = [], $code = 1)
    {
        $this->writeLn($str, $context);

        return $code;
    }
}