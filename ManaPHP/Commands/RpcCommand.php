<?php

namespace ManaPHP\Commands;

use ManaPHP\Helper\LocalFS;
use ReflectionClass;
use ReflectionMethod;

class RpcCommand extends \ManaPHP\Cli\Command
{
    /**
     * generate services stub for client
     *
     * @param string $output
     *
     * @return void
     * @throws \ManaPHP\Exception\RuntimeException
     */
    public function servicesAction($output = '@tmp/rpc_services')
    {
        foreach (LocalFS::glob('@app/Controllers/?*Controller.php') as $file) {
            $className = 'App\\Controllers\\' . basename($file, '.php');

            $methods = [];
            foreach (get_class_methods($className) as $method) {
                if (preg_match('#^(.*)Action$#', $method, $match)) {
                    $methods[] = $method;
                }
            }

            if ($methods !== []) {
                $content = $this->renderService($className, $methods);
                $file = rtrim($output, '/') . '/' . basename($className, 'Controller') . 'Service.php';
                LocalFS::filePut($file, $content);

                $serviceName = basename($className, 'Controller') . 'Service';
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
    protected function renderService($class, $methods)
    {
        $serviceName = basename($class, 'Controller') . 'Service';

        $lines = file((new ReflectionClass($class))->getFileName());
        $content = <<<EOT
<?php

namespace App\Services;

use ManaPHP\Rpc\Service;

class $serviceName extends Service
{
EOT;
        foreach ($methods as $method) {
            $content .= PHP_EOL;
            $rMethod = new ReflectionMethod($class, $method);
            if ($doc = $rMethod->getDocComment()) {
                $content .= "\t" . $doc . PHP_EOL;
            }

            $signature = $lines[$rMethod->getStartLine() - 1];
            $content .= preg_replace('#(\s.*)Action#', '\\1', $signature);
            $content .= <<<EOT
    {
        return \$this->__rpcCall(__METHOD__, func_get_args());
    }
EOT;
            $content .= PHP_EOL;
        }

        $content .= '}';

        return $content;
    }
}