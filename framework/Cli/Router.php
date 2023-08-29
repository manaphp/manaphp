<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

class Router implements RouterInterface
{
    protected string $entrypoint;
    protected ?string $command;
    protected ?string $action;
    protected array $params;

    public function route(array $args): void
    {
        $this->entrypoint = array_shift($args);

        if ($args === [] || $args === ['--help'] || $args === ['-h']) {
            $this->command = 'help';
            $this->action = 'commands';
            $this->params = [];
        } else {
            $cmd = array_shift($args);
            if (str_contains($cmd, ':')) {
                list($command, $action) = explode(':', $cmd, 2);
            } elseif (str_contains($cmd, '/')) {
                list($command, $action) = explode('/', $cmd, 2);
            } else {
                $command = $cmd;
                $action = null;
            }

            if ($args === ['--help'] || $args === ['-h']) {
                $args = ['--command', $command];

                if ($action !== null) {
                    $args[] = '--action';
                    $args[] = $action;
                }

                $command = 'help';
                $action = 'command';
            }

            $this->command = $command;
            $this->action = $action ?? 'default';
            $this->params = $args;
        }
    }

    public function getEntrypoint(): string
    {
        return $this->entrypoint;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}