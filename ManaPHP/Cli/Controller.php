<?php
namespace ManaPHP\Cli;

use ManaPHP\Component;

/**
 * Class ManaPHP\Cli\Controller
 *
 * @package ManaPHP\Cli
 *
 * @property \ManaPHP\Http\ClientInterface       $httpClient
 * @property \ManaPHP\Mvc\Model\ManagerInterface $modelsManager
 * @property \ManaPHP\CounterInterface           $counter
 * @property \ManaPHP\CacheInterface             $cache
 * @property \ManaPHP\DbInterface                $db
 * @property \ManaPHP\Security\CryptInterface    $crypt
 * @property \ManaPHP\Http\Session\BagInterface  $persistent
 * @property \ManaPHP\Di|\ManaPHP\DiInterface    $di
 * @property \ManaPHP\LoggerInterface            $logger
 * @property \Application\Configure              $configure
 * @property \ManaPHP\Cache\AdapterInterface     $viewsCache
 * @property \ManaPHP\FilesystemInterface        $filesystem
 * @property \ManaPHP\Security\RandomInterface   $random
 * @property \ManaPHP\Message\QueueInterface     $messageQueue
 * @property \ManaPHP\Cli\ConsoleInterface       $console
 * @property \ManaPHP\Cli\ArgumentsInterface     $arguments
 * @property \ManaPHP\Text\CrosswordInterface    $crossword
 * @property \ManaPHP\Cli\RouterInterface        $cliRouter
 */
abstract class Controller extends Component implements ControllerInterface
{
    /**
     * @return array
     */
    public function getCommands()
    {
        $controller = lcfirst(basename(get_called_class(), 'Controller'));

        $commands = [];
        $rc = new \ReflectionClass($this);
        foreach (get_class_methods($this) as $method) {
            if (preg_match('#^(.*)Command$#', $method, $match) !== 1) {
                continue;
            }
            $command = $controller . ($match[1] !== 'default' ? (':' . $match[1]) : '');

            $rm = $rc->getMethod($match[0]);
            $comment = $rm->getDocComment();
            if ($comment && preg_match('#\*\s+@description\s+(.*)#', $comment, $match) === 1) {
                $commands[$command] = $match[1];
            } else {
                $commands[$command] = '';
            }
        }

        if (count($commands) === 1) {
            $commands = [$controller => array_values($commands)[0]];
        }

        return $commands;
    }
}