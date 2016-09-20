<?php
namespace ManaPHP\Cli;

use ManaPHP\Component;

/**
 * Class Controller
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
 */
abstract class Controller extends Component implements ControllerInterface
{

}