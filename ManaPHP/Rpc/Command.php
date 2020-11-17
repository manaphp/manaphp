<?php

namespace ManaPHP\Rpc;

use ManaPHP\Helper\LocalFS;
use ReflectionClass;
use ReflectionMethod;

class Command extends \ManaPHP\Cli\Command
{
    /**
     * generate services stub for client
     *
     * @param string $output
     *
     * @throws \ManaPHP\Exception\RuntimeException
     */
    public function servicesAction($output = '@tmp/rpc_services')
    {
        foreach (LocalFS::glob('@app/Commands/*Command.php') as $file) {
            $className = 'App\\Commands\\' . basename($file, '.php');

            $methods = [];
            foreach (get_class_methods($className) as $method) {
                if (preg_match('#^(.*)Action$#', $method, $match)) {
                    $methods[] = $method;
                }
            }

            if ($methods !== []) {
                $content = $this->_renderService($className, $methods);
                $file = rtrim($output, '/') . '/' . basename($className, 'Command') . 'Services.php';
                LocalFS::filePut($file, $content);

                $serviceName = basename($className, 'Command') . 'Service';
                $this->console->writeLn("`$serviceName` saved to `$file`");
            }
        }
    }

    /**
     * @param string $class
     * @param array  $methods
     *
     * @return string
     */
    protected function _renderService($class, $methods)
    {
        $serviceName = basename($class, 'Command') . 'Service';

        $lines = file((new ReflectionClass($class))->getFileName());
        $content = <<<EOT
<?php

namespace App\Services;

use ManaPHP\Rpc\Client\Service;

class $serviceName extends Service
{
EOT;
        foreach ($methods as $method) {
            $content .= PHP_EOL;
            $rm = new ReflectionMethod($class, $method);
            if ($doc = $rm->getDocComment()) {
                $content .= "\t" . $doc . PHP_EOL;
            }

            $signature = $lines[$rm->getStartLine() - 1];
            $content .= preg_replace('#(\s.*)Action#', '\\1', $signature);
            $content .= <<<EOT
    {
        return \$this->invoke(__METHOD__, func_get_args());
    }
EOT;
            $content .= PHP_EOL;
        }

        $content .= '}';

        return $content;
    }
}