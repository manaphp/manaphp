<?php
namespace ManaPHP\Cli;

use ManaPHP\Component;

/**
 * Class ManaPHP\Cli\Controller
 *
 * @package controller
 *
 * @property \ManaPHP\Http\ClientInterface       $httpClient
 * @property \ManaPHP\Db\Model\ManagerInterface $modelsManager
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
 * @property \ManaPHP\Redis                      $redis
 * @property \ManaPHP\Cli\EnvironmentInterface   $environment
 * @property \ManaPHP\Net\ConnectivityInterface  $netConnectivity
 * @property \MongoDB\Client                     $mongodb
 * @property \Elasticsearch\Client               $elasticsearch
 * @property \ManaPHP\ZookeeperInterface         $zookeeper
 */
abstract class Controller extends Component implements ControllerInterface
{
    /**
     * @CliCommand show the help information
     */
    public function helpCommand()
    {
        $parts = explode('\\', get_class($this));
        $controller = strtolower(basename(end($parts), 'Controller'));

        foreach (get_class_methods($this) as $method) {
            if (preg_match('#^.*Command$#', $method) !== 1) {
                continue;
            }

            $command = $controller . ':' . basename($method, 'Command');
            $params = [];
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $rm = new \ReflectionMethod($this, $method);
            $lines = explode("\n", $rm->getDocComment());
            foreach ($lines as $line) {
                $line = trim($line, ' \t*');
                $parts = explode(' ', $line, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                list($tag, $description) = $parts;
                $description = trim($description);
                if ($tag === '@CliCommand') {
                    $command = str_pad($controller . ':' . basename($method, 'Command'), 13) . ' ' . $description;
                } elseif ($tag === '@CliParam') {
                    $parts = explode(' ', $description, 2);
                    $params[trim($parts[0])] = isset($parts[1]) ? trim($parts[1]) : '';
                }
            }

            $this->console->writeLn($command);
            if (count($params) !== 0) {
                $this->console->writeLn('  Options:');

                foreach ($params as $name => $value) {
                    $parts = explode(',', $name);
                    if (count($parts) === 2) {
                        $option = strlen($parts[0]) > strlen($parts[1]) ? ($parts[1] . ',' . $parts[0]) : ($parts[0] . ',' . $parts[1]);
                    } else {
                        $option = '   ' . $name;
                    }

                    if ($option !== $name) {
                        $params[$option] = $value;
                        unset($params[$name]);
                    }
                }

                $maxLength = max(max(array_map('strlen', array_keys($params))), 1);
                foreach ($params as $name => $value) {
                    $this->console->writeLn('    ' . str_pad($name, $maxLength + 1, ' ') . ' ' . $value);
                }
            }
        }
    }
}