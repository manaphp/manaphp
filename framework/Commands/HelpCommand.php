<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Cli\CommandsInterface;
use ManaPHP\Cli\Console;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Exception\JsonException;
use ManaPHP\Helper\Str;
use ManaPHP\Version;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use function count;
use function is_string;
use function strlen;

class HelpCommand extends Command
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected CommandsInterface $commands;

    #[Config] protected string $app_id;
    #[Config] protected string $app_env;
    #[Config] protected bool $app_debug;

    /**
     * list all commands
     *
     * @return int
     */
    public function commandsAction(): int
    {
        $builtin_commands = [];
        $app_commands = [];
        foreach ($this->commands->getCommands() as $name => $definition) {
            if (is_string($definition)) {
                if (str_starts_with($definition, 'App\\')) {
                    $app_commands[$name] = $definition;
                } else {
                    $builtin_commands[$name] = $definition;
                }
            }
        }

        $this->console->writeLn(
            sprintf(
                'ManaPHP %s (id: %s, env: %s, debug: %s)',
                $this->console->colorize(Version::get(), Console::FC_GREEN | Console::AT_BOLD),
                $this->console->colorize($this->app_id, Console::FC_YELLOW | Console::AT_BOLD),
                $this->console->colorize($this->app_env, Console::FC_YELLOW | Console::AT_BOLD),
                $this->console->colorize(
                    $this->app_debug ? 'true' : 'false', Console::FC_YELLOW | Console::AT_BOLD
                )
            )
        );
        $this->console->writeLn();

        $this->console->writeLn('manaphp commands:', [], Console::FC_GREEN | Console::AT_BOLD);
        ksort($builtin_commands);
        foreach ($builtin_commands as $name => $definition) {
            if ($name === 'helpCommand') {
                continue;
            }

            $description = $this->getCommandDescription($definition);
            $plainName = ucfirst($name);
            $command = Str::snakelize(basename($plainName, 'Command'));
            $this->console->writeLn(' - ' . $this->console->colorize($command, Console::FC_YELLOW) . $description);
            $actions = $this->getActions($definition);

            $width = max(max(array_map('\strlen', array_keys($actions))), 18);
            foreach ($actions as $action => $description) {
                $colored_action = $this->console->colorize($action, Console::FC_CYAN, $width);
                $this->console->writeLn('    ' . $colored_action . ' ' . $description);
            }
        }

        ksort($app_commands);
        $this->console->writeLn('application commands:', [], Console::FC_GREEN | Console::AT_BOLD);
        foreach ($app_commands as $name => $definition) {
            $description = $this->getCommandDescription($definition);
            $plainName = ucfirst($name);
            $command = Str::snakelize(basename($plainName, 'Command'));
            $this->console->writeLn(' - ' . $this->console->colorize($command, Console::FC_YELLOW) . $description);
            $actions = $this->getActions($definition);

            $width = max(max(array_map('\strlen', array_keys($actions))), 18);
            foreach ($actions as $action => $description) {
                $colored_action = $this->console->colorize($action, Console::FC_CYAN, $width);
                $this->console->writeLn('    ' . $colored_action . ' ' . $description);
            }
        }
        return 0;
    }

    /**
     * @param string $class
     *
     * @return string
     */
    protected function getCommandDescription(string $class): string
    {
        $rClass = new ReflectionClass($class);
        if (($comment = $rClass->getDocComment()) === false) {
            return '';
        }

        $lines = preg_split('#[\r\n]+#', $comment, 3);
        if (($description = $lines[1] ?? null) === null) {
            return '';
        }
        $description = trim($description, " \t\n\r\0\x0B*");
        if (str_starts_with($description, 'Class') || str_starts_with($description, '@')) {
            return '';
        }

        return "\t\t" . $description;
    }

    /**
     * @param string $commandClassName
     *
     * @return string[]
     */
    protected function getActions(string $commandClassName): array
    {
        $actions = [];
        $rClass = new ReflectionClass($commandClassName);
        foreach (get_class_methods($commandClassName) as $method) {
            if (preg_match('#^(.*)Action$#', $method, $match) !== 1) {
                continue;
            }
            if ($match[1] === 'help') {
                continue;
            }

            $action = $match[1];

            $description = '';
            foreach (preg_split('#[\r\n]+#', $rClass->getMethod($match[0])->getDocComment() ?: '') as $line) {
                $line = trim($line, "\t /*\r\n");
                if (!$line) {
                    continue;
                }

                if ($line[0] !== '@') {
                    $description = $line;
                }
                break;
            }
            $actions[$action] = $description;
        }

        ksort($actions);

        return $actions;
    }

    /**
     * @param ReflectionMethod $rMethod
     * @param string           $method
     *
     * @throws JsonException
     */
    protected function commandHelps(ReflectionMethod $rMethod, string $method): void
    {
        $lines = [];
        foreach (preg_split('#[\r\n]+#', $rMethod->getDocComment()) as $line) {
            $lines[] = trim($line, "\t /*\r\n");
        }

        $description = '';
        foreach ($lines as $line) {
            if (!$line) {
                continue;
            }

            if ($line[0] !== '@') {
                $description = $line;
            }
            break;
        }

        $method_name = str_pad(basename($method, 'Action'), 10);
        $colored_action = $this->console->colorize($method_name, Console::FC_YELLOW) . ' ' . $description;
        $this->console->writeLn($colored_action);

        $options = [];

        $defaultValues = [];
        foreach ($rMethod->getParameters() as $rParameter) {
            $name = $rParameter->getName();
            if ($rParameter->isDefaultValueAvailable()) {
                $defaultValues[$name] = $rParameter->getDefaultValue();
            }

            if (($rType = $rParameter->getType()) === null) {
                $options[$name] = '';
            } elseif (preg_match('#^\w+$#', $rType->getName())) {
                $options[$name] = '';
            }
        }

        foreach ($lines as $line) {
            if (!str_contains($line, '@param')) {
                continue;
            }

            $parts = preg_split('#\s+#', $line, 4);
            if (count($parts) < 3 || $parts[0] !== '@param') {
                continue;
            }
            $name = substr($parts[2], 1);
            $type = $parts[1];

            if (!isset($options[$name])) {
                continue;
            }

            if (isset($defaultValues[$name])) {
                if ($type === 'bool' || $type === 'boolean') {
                    $defaultValues[$name] = $defaultValues[$name] ? 'YES' : 'NO';
                } elseif ($type === 'int' || $type === 'integer') {
                    $defaultValues[$name] = (int)$defaultValues[$name];
                } elseif ($type === 'float' || $type === 'double') {
                    $defaultValues[$name] = (float)$defaultValues[$name];
                } elseif ($type === 'string') {
                    $defaultValues[$name] = json_stringify($defaultValues[$name]);
                } elseif ($type === 'array') {
                    $defaultValues[$name] = json_stringify($defaultValues[$name]);
                }
            }

            $options[$name] = isset($parts[3]) ? trim($parts[3]) : '';
        }

        if ($options) {
            $shortNames = [];
            foreach ($options as $name => $description) {
                $short = $name[0];
                if (isset($shortNames[$short])) {
                    $shortNames[$short] = false;
                } else {
                    $shortNames[$short] = $name;
                }
            }
            $shortNames = array_flip(array_filter($shortNames));

            $width = 1;
            foreach ($options as $name => $description) {
                $width = max($width, strlen($name) + 2 + (isset($shortNames[$name]) ? 4 : 0));
            }
            $this->console->writeLn('  Options:');

            foreach ($options as $name => $value) {
                $option = '--' . $name;
                if (isset($shortNames[$name])) {
                    $option .= ', -' . $shortNames[$name];
                }

                $ov = $this->console->colorize($option, Console::FC_CYAN, $width);
                $vv = $value ? "  $value" : '';
                $dv = isset($defaultValues[$name]) ? " (default: $defaultValues[$name])" : '';
                $this->console->writeLn(sprintf('    %s %s %s', $ov, $vv, $dv));
            }
        }
    }

    /**
     * list command
     *
     * @param string $command
     * @param string $action
     *
     * @return int
     */
    public function commandAction(string $command, string $action = ''): int
    {
        $camelizedCommand = Str::camelize($command);
        if (($definition = $this->commands->getCommands()[$camelizedCommand] ?? null) === null) {
            return $this->console->error("$camelizedCommand Command not found");
        }
        $instance = $this->container->get($definition);

        foreach (get_class_methods($instance) as $method) {
            if (!preg_match('#^([a-z].*)Action$#', $method, $match)) {
                continue;
            }

            if ($action !== '' && $match[1] !== $action) {
                continue;
            }

            $rMethod = new ReflectionMethod($instance, $method);
            if (!$rMethod->isPublic()) {
                continue;
            }

            $helpMethod = basename($method, 'Action') . 'Help';
            if (method_exists($instance, $helpMethod)) {
                $instance->$helpMethod();
            } else {
                $this->commandHelps($rMethod, $method);
            }
        }

        return 0;
    }
}