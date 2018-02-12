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
     * @var int
     */
    protected $_width = 80;

    const FC_BLACK = 0x01;
    const FC_RED = 0x02;
    const FC_GREEN = 0x04;
    const FC_YELLOW = 0x08;
    const FC_BLUE = 0x10;
    const FC_MAGENTA = 0x20;
    const FC_CYAN = 0x40;
    const FC_WHITE = 0x80;

    const BC_BLACK = 0x0100;
    const BC_RED = 0x0200;
    const BC_GREEN = 0x0400;
    const BC_YELLOW = 0x0800;
    const BC_BLUE = 0x1000;
    const BC_MAGENTA = 0x2000;
    const BC_CYAN = 0x4000;
    const BC_WHITE = 0x8000;

    const AT_BOLD = 0x010000;
    const AT_ITALICS = 0x020000;
    const AT_UNDERLINE = 0x040000;
    const AT_BLINK = 0x080000;
    const AT_INVERSE = 0x100000;

    /**
     * @return bool
     */
    public function isSupportColor()
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return true;
        }

        return false !== \getenv('ANSICON') || 'ON' === \getenv('ConEmuANSI') || 'xterm' === \getenv('TERM');
    }

    /**
     * @param string $text
     * @param int    $options
     *
     * @return string
     */
    public function colorize($text, $options = 0)
    {
        $map = [
            self::AT_BOLD => "\033[1m",
            self::AT_ITALICS => "\033[3m",
            self::AT_UNDERLINE => "\033[4m",
            self::AT_BLINK => "\033[5m",
            self::AT_INVERSE => "\033[7m",

            self::BC_BLACK => "\033[40m",
            self::BC_RED => "\033[41m",
            self::BC_GREEN => "\033[42m",
            self::BC_YELLOW => "\033[43m",
            self::BC_BLUE => "\033[44m",
            self::BC_MAGENTA => "\033[45m",
            self::BC_CYAN => "\033[46m",
            self::BC_WHITE => "\033[47m",

            self::FC_BLACK => "\033[30m",
            self::FC_RED => "\033[31m",
            self::FC_GREEN => "\033[32m",
            self::FC_YELLOW => "\033[33m",
            self::FC_BLUE => "\033[34m",
            self::FC_MAGENTA => "\033[35m",
            self::FC_CYAN => "\033[36m",
            self::FC_WHITE => "\033[37m",
        ];

        if (!$this->isSupportColor()) {
            return $text;
        }

        $c = '';
        for ($i = 0; $i < 32; $i++) {
            if ((1 << $i) & $options) {
                $c .= $map[(1 << $i) & $options];
            }
        }

        return $c . $text . "\033[0m";
    }

    /**
     * @param string|array $str
     * @param int          $options
     *
     * @return static
     */
    public function write($str, $options = 0)
    {
        if (is_array($str)) {
            if (isset($str[0])) {
                $context = $str;
                $str = $str[0];
                unset($context[0]);
            } else {
                $str = json_encode($str, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $context = [];
            }
        } elseif ($str instanceof \JsonSerializable) {
            $str = json_encode($str, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $context = [];
        } else {
            $str = (string)$str;
            $context = [];
        }

        if (strpos($str, ':') === false) {
            echo $this->colorize($str, $options);
        } else {
            if (!isset($context['last_error_message'])) {
                $context['last_error_message'] = error_get_last()['message'];
            }

            $replaces = [];

            foreach ($context as $k => $v) {
                if (!$options && strpos($v, "\033[") === false) {
                    if (is_int($v) || is_float($v)) {
                        $v = $this->colorize($v, self::FC_GREEN);
                    } elseif (strpos($str, "`:$k`") !== false) {
                        $v = $this->colorize($v, self::FC_CYAN);
                    }
                }
                $replaces[':' . $k] = $v;
            }

            echo $this->colorize(strtr($str, $replaces), $options);
        }

        return $this;
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function sampleColorizer()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $rc = new \ReflectionClass($this);
        $bc_list = [0 => 0];
        $fc_list = [0 => 0];

        foreach ($rc->getConstants() as $name => $value) {
            if (strpos($name, 'BC_') === 0) {
                $bc_list[$name] = $value;
            } elseif (strpos($name, 'FC_') === 0) {
                $fc_list[$name] = $value;
            }
        }

        foreach ($bc_list as $bc_name => $bc_value) {
            foreach ($fc_list as $fc_name => $fc_value) {
                $headers = [];
                if ($bc_value) {
                    $headers[] = 'Console::' . $bc_name;
                }

                if ($fc_value) {
                    $headers[] = 'Console::' . $fc_name;
                }

                echo str_pad(implode('|', $headers), 40), $this->colorize('ManaPHP http://www.manaphp.com/', $bc_value | $fc_value), PHP_EOL;
            }
        }

        $this->write('');
        $this->info('This is info text');
        $this->warn('This is warn text');
        $this->success('This is success text');
        sleep(1);
        $this->error('This is error text');

        $progress = 0;
        while ($progress < 100) {

            $progress += mt_rand(1, $progress > 10 ? 20 : 3);
            $this->progress('current process is :value', $progress);
            sleep(1);
        }
    }

    /**
     * @param string|array $str
     * @param int          $options
     *
     * @return static
     */
    public function writeLn($str = '', $options = 0)
    {
        $this->write($str, $options);
        $this->write(PHP_EOL);

        return $this;
    }

    /**
     * @param array|string $message
     */
    public function info($message)
    {
        $this->write($message, self::FC_YELLOW);
        $this->write(PHP_EOL);
    }

    /**
     * @param array|string $message
     */
    public function warn($message)
    {
        $this->write($message, self::FC_MAGENTA);
        $this->write(PHP_EOL);
    }

    /**
     * @param array|string $message
     */
    public function success($message)
    {
        $this->write($message, self::FC_BLUE);
        $this->write(PHP_EOL);
    }

    /**
     * @param array|string $message
     * @param int          $code
     *
     * @return int
     */
    public function error($message, $code = 1)
    {
        $this->write($message, self::FC_RED);
        $this->write(PHP_EOL);

        return $code;
    }

    /**
     * @param  string|array    $message
     * @param int|float|string $value
     */
    public function progress($message, $value = null)
    {
        if ($value !== null) {
            if (is_int($value) || is_float($value)) {
                $percent = sprintf('%2.2f', $value) . '%';
            } else {
                $percent = $value;
            }

            if (is_array($message)) {
                $message['value'] = $this->colorize($percent, self::FC_GREEN);
            } else {
                $message = [$message, 'value' => $this->colorize($percent, self::FC_GREEN)];
            }
        }

        $this->write(str_pad("\r", $this->_width));
        $this->write("\r");
        $this->write($message);

        if ($value === null) {
            $this->write(PHP_EOL);
        }
    }
}