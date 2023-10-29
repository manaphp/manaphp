<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Autowired;
use Swoole\Process;

class DaemonCommand extends Command
{
    #[Autowired] protected array $commands = [];
    #[Autowired] protected ?string $entrypoint;

    public function startAction(): void
    {
        $entrypoint = $this->entrypoint ?? 'php ' . get_included_files()[0];
        foreach ($this->commands as $command) {
            $process = new Process(function () use ($command, $entrypoint) {
                for (; ;) {
                    exec(sprintf('%s %s', $entrypoint, $command), $output, $result_code);
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

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($this->commands as $command) {
            Process::wait();
        }
    }
}