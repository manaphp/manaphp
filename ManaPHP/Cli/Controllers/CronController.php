<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Cli\Cronable;
use ManaPHP\Di;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ReflectionClass;
use Swoole\Coroutine;
use Throwable;

/**
 * Class CronController
 *
 * @package ManaPHP\Cli\Controllers
 */
class CronController extends Controller
{
    /**
     * @var \ManaPHP\Cli\Cron\ScheduleParser
     */
    protected $_scheduleParser;

    public function __construct()
    {
        $this->_scheduleParser = Di::getDefault()->getShared('ManaPHP\Cli\Cron\ScheduleParser');
    }

    /**
     * @param \ManaPHP\Cli\Cronable $cron
     */
    public function routine($cron)
    {
        $schedule = $cron->schedule();

        if (is_int($schedule) || is_float($schedule)) {
            do {
                $last = microtime(true);
                try {
                    $cron->defaultCommand();
                } catch (Throwable $throwable) {
                    $this->logger->error($throwable);
                }
            } while (@time_sleep_until($last + $schedule));
        } else {
            $time = time();
            while (true) {
                if ($this->_scheduleParser->match($schedule, $time)) {
                    try {
                        $cron->defaultCommand();
                    } catch (Throwable $throwable) {
                        $this->logger->error($throwable);
                    }
                }
                @time_sleep_until(++$time);
            }
        }
    }

    /**
     * @param string $name
     *
     * @return Cronable[]
     */
    protected function _getCrons($name = '')
    {
        $crons = [];
        if ($name) {
            $class_name = $this->alias->get('@ns.cli') . '\\' . Str::camelize($name) . 'Controller';
            $rc = new ReflectionClass($class_name);

            if (!in_array(Cronable::class, $rc->getInterfaceNames(), true)) {
                throw new RuntimeException('is not cronable');
            }
            $crons[] = $this->_di->get($class_name);
        } else {
            foreach (LocalFS::glob('@cli/*Controller.php') as $file) {
                $class_name = $this->alias->get('@ns.cli') . '\\' . basename($file, '.php');
                $rc = new ReflectionClass($class_name);
                if (in_array(Cronable::class, $rc->getInterfaceNames(), true)) {
                    $crons[] = $this->_di->get($class_name);
                }
            }
        }

        return $crons;
    }

    /**
     * @param string $name
     */
    public function runCommand($name = '')
    {
        foreach ($this->_getCrons($name) as $cron) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            Coroutine::create([$this, 'routine'], $cron);
        }
    }

    /**
     * @param string $name
     */
    public function listCommand($name = '')
    {
        foreach ($this->_getCrons($name) as $cron) {
            $schedule = $cron->schedule();
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $this->console->writeln(get_class($cron) . ': ' . $schedule);

            $count = 0;

            if (is_int($schedule) || is_float($schedule)) {
                $current = microtime(true);
                for ($i = 0; ; $i++) {
                    $next = $current + $schedule * $i;
                    $this->console->writeLn('     ' . date('Y-m-d H:i:s', $next) . sprintf('.%03d', ($next - (int)$next) * 1000));
                    if (++$count === 5) {
                        break;
                    }
                }
            } else {
                $current = time();
                for ($i = 0; ; $i++) {
                    $time = $current + $i;
                    if ($this->_scheduleParser->match($schedule, $time)) {
                        $this->console->writeln('    ' . date('Y-m-d H:i:s', $time));
                        if (++$count === 5) {
                            break;
                        }
                    }
                }
            }

            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->console->writeLn();
        }
    }
}