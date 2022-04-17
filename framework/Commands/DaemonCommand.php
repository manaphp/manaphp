<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use Swoole\Process;

class DaemonCommand extends Command
{
    protected array $commands = [];
    protected string $entrypoint;

    public function __construct(array $commands, ?string $entrypoint = null)
    {
        $this->commands = $commands;
        $this->entrypoint = $entrypoint ?? 'php ' . get_included_files()[0];
    }

    public function startAction()
    {
        foreach ($this->commands as $command) {
            $process = new Process(function () use ($command) {
                for (; ;) {
                    exec(sprintf('%s %s', $this->entrypoint, $command), $output, $result_code);
                    foreach ($output as $line) {
                        $this->console->writeLn(str_replace(' [0m', '', $line));
                    }

                    if ($result_code === 0) {
                        break;
                    } else {
                        sleep(1);
                    }
                }
            });
            $process->start();
        }

        foreach ($this->commands as $command) {
            Process::wait(true);
        }
    }
}