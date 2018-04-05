<?php

namespace ManaPHP\Cli;

use ManaPHP\Component;

/**
 * Class ManaPHP\Cli\Controller
 *
 * @package controller
 *
 * @property \ManaPHP\Curl\EasyInterface              $httpClient
 * @property \ManaPHP\CounterInterface                $counter
 * @property \ManaPHP\CacheInterface                  $cache
 * @property \ManaPHP\DbInterface                     $db
 * @property \ManaPHP\Security\CryptInterface         $crypt
 * @property \ManaPHP\Di|\ManaPHP\DiInterface         $di
 * @property \ManaPHP\LoggerInterface                 $logger
 * @property \ManaPHP\Configuration\Configure         $configure
 * @property \ManaPHP\Configuration\SettingsInterface $settings
 * @property \ManaPHP\Cache\EngineInterface           $viewsCache
 * @property \ManaPHP\FilesystemInterface             $filesystem
 * @property \ManaPHP\Security\RandomInterface        $random
 * @property \ManaPHP\Message\QueueInterface          $messageQueue
 * @property \ManaPHP\Cli\ConsoleInterface            $console
 * @property \ManaPHP\Cli\ArgumentsInterface          $arguments
 * @property \ManaPHP\Text\CrosswordInterface         $crossword
 * @property \ManaPHP\Redis                           $redis
 * @property \ManaPHP\Cli\EnvironmentInterface        $environment
 * @property \ManaPHP\Net\ConnectivityInterface       $netConnectivity
 * @property \ManaPHP\Mongodb                         $mongodb
 * @property \Elasticsearch\Client                    $elasticsearch
 * @property \ManaPHP\ZookeeperInterface              $zookeeper
 * @property \ManaPHP\AmqpInterface                   $rabbitmq
 */
abstract class Controller extends Component implements ControllerInterface
{
    /**
     * show this help information
     */
    public function helpCommand()
    {
        foreach (get_class_methods($this) as $method) {
            if (!preg_match('#^[a-z].*Command$#', $method)) {
                continue;
            }
            $rm = new \ReflectionMethod($this, $method);
            if (!$rm->isPublic()) {
                continue;
            }

            $lines = [];
            foreach (preg_split('#[\r\n]+#', $rm->getDocComment()) as $line) {
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

            $command = $this->console->colorize(str_pad(basename($method, 'Command'), 10), Console::FC_YELLOW) . ' ' . $description;
            $this->console->writeLn($command);

            $defaultValues = [];
            foreach ($rm->getParameters() as $parameter) {
                if ($parameter->isDefaultValueAvailable()) {
                    $defaultValues[$parameter->getName()] = $parameter->getDefaultValue();
                }
            }
	    
            $params = [];
            foreach ($lines as $line) {
                if (strpos($line, '@param') === false) {
                    continue;
                }

                $parts = preg_split('#\s+#', $line, 4);
                if (count($parts) < 3 || $parts[0] !== '@param') {
                    continue;
                }
                $name = substr($parts[2], 1);
                $type = $parts[1];

                if (isset($defaultValues[$name])) {
                    if ($type === 'bool' || $type === 'boolean') {
                        $defaultValues[$name] = $defaultValues[$name] ? 'YES' : 'NO';
                    } elseif ($type === 'int' || $type === 'integer') {
                        $defaultValues[$name] = (int)$defaultValues[$name];
                    } elseif ($type === 'float' || $type === 'double') {
                        $defaultValues[$name] = (double)$defaultValues[$name];
                    } elseif ($type === 'string') {
                        $defaultValues[$name] = json_encode($defaultValues[$name]);
                    } elseif ($type === 'array') {
                        $defaultValues[$name] = json_encode($defaultValues[$name]);
                    }
                }

                $params[$name] = isset($parts[3]) ? trim($parts[3]) : '';
            }

            if ($params) {
                $shortNames = [];
                foreach ($params as $name => $description) {
                    $short = $name[0];
                    if (isset($shortNames[$short])) {
                        $shortNames[$short] = false;
                    } else {
                        $shortNames[$short] = $name;
                    }
                }
                $shortNames = array_flip(array_filter($shortNames));

                $maxLength = 1;
                foreach ($params as $name => $description) {
                    $maxLength = max($maxLength, strlen($name) + 2 + (isset($shortNames[$name]) ? 4 : 0));
                }
                $this->console->writeLn('  Options:');

                foreach ($params as $name => $value) {
                    $option = '--' . $name;
                    if (isset($shortNames[$name])) {
                        $option .= ', -' . $shortNames[$name];
                    }
                    $option = str_pad($option, $maxLength + 1, ' ');
                    $this->console->writeLn('    ' . $this->console->colorize($option,
                            Console::FC_CYAN) . ($value ? "  $value" : '') . (isset($defaultValues[$name]) ? " (default: $defaultValues[$name])" : ''));
                }
            }
        }
    }
}