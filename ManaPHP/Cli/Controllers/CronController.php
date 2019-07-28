<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Cron\ScheduleParser;
use ManaPHP\Utility\Text;
use Swoole\Coroutine;
use Throwable;

/**
 * Class CronController
 * @package ManaPHP\Cli\Controllers
 * @property-read \ManaPHP\Cli\CommandInvoker $commandInvoker
 */
class CronController extends Controller
{
    /**
     * @var \ManaPHP\Cron\ScheduleParser
     */
    protected $_scheduleParser;

    public function __construct()
    {
        $this->_scheduleParser = new ScheduleParser();
    }

    /**
     * @param \ManaPHP\CronInterface $cron
     */
    public function routine($cron)
    {
        $schedule = $cron->schedule();

        if (is_int($schedule) || is_float($schedule)) {
            do {
                $last = microtime(true);
                try {
                    $cron->run();
                } catch (Throwable $throwable) {
                    $this->logger->error($throwable);
                }
            } while (@time_sleep_until($last + $schedule));
        } else {
            $time = time();
            while (true) {
                if ($this->_scheduleParser->match($schedule, $time)) {
                    try {
                        $cron->run();
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
     */
    public function runCommand($name = '')
    {
        $crons = [];
        if ($name) {
            $class_name = $this->alias->resolveNS('@ns.app\Crons\\' . Text::camelize($name) . 'Cron');
            $crons[] = new $class_name();
        } else {
            foreach ($this->filesystem->glob('@app/Crons/*Cron.php') as $file) {
                $class_name = $this->alias->resolveNS('@ns.app\Crons\\' . basename($file, '.php'));
                $crons[] = new $class_name();
            }
        }

        foreach ($crons as $cron) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            Coroutine::create([$this, 'routine'], $cron);
        }
    }

    /**
     * @param string|float|int $schedule
     * @param string           $name
     */
    public function testCommand($schedule = '', $name = '')
    {
        $count = 0;
        if ($name) {
            $class_name = $this->alias->resolveNS('@ns.app\Crons\\' . Text::camelize($name) . 'Cron');
            /** @var \ManaPHP\CronInterface $cron */
            $cron = new $class_name;
            $schedule = $cron->schedule();
        }

        if (is_int($schedule) || is_float($schedule)) {
            $current = microtime(true);
            for ($i = 0; ; $i++) {
                $next = $current + $schedule * $i;
                $this->console->writeLn(date('Y-m-d H:i:s', $next) . sprintf('.%03d', ($next - (int)$next) * 1000));
                if (++$count === 10) {
                    break;
                }
            }
        } else {
            $current = time();
            for ($i = 0; ; $i++) {
                $time = $current + $i;
                if ($this->_scheduleParser->match($schedule, $time)) {
                    $this->console->writeLn(date('Y-m-d H:i:s', $time));
                    if (++$count === 10) {
                        break;
                    }
                }
            }
        }
    }

}