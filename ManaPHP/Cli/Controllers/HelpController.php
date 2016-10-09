<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

class HelpController extends Controller
{
    /**
     * @description list all commands
     * @return int
     */
    public function listCommand()
    {
        $controllerNames = [];
        $commands = [];

        foreach ($this->filesystem->glob('@app/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+/\w+/Controllers/(\w+)Controller)\.php$#', $file, $matches)) {
                $controllerClassName = str_replace('/', '\\', $matches[1]);
                $controllerNames[] = $matches[2];
                /**
                 * @var \ManaPHP\Cli\ControllerInterface $instance
                 */
                $instance = new $controllerClassName();
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $commands = array_merge($commands, $instance->getCommands());
            }
        }

        foreach ($this->filesystem->glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+/\w+/Controllers/(\w+)Controller)\.php$#', $file, $matches)) {
                $controllerClassName = str_replace('/', '\\', $matches[1]);
                if (in_array($matches[2], $controllerNames, true)) {
                    continue;
                }
                /**
                 * @var \ManaPHP\Cli\ControllerInterface $instance
                 */
                $instance = new $controllerClassName();
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $commands = array_merge($commands, $instance->getCommands());
            }
        }

        ksort($commands);
        foreach ($commands as $command => $description) {
            $this->console->writeLn(str_pad($command, 18, ' ') . '  ' . $description);
        }
        return 0;
    }
}