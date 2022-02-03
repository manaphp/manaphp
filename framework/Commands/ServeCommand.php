<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\ConfigInterface      $config
 * @property-read \ManaPHP\Http\RouterInterface $router
 * @property-read \ManaPHP\Cli\RequestInterface $request
 */
class ServeCommand extends Command
{
    /**
     * start with php builtin server
     *
     * @param string $ip
     * @param int    $port
     *
     * @return void
     */
    public function runAction(string $ip = '0.0.0.0', int $port = 0): void
    {
        $router_str = <<<'STR'
<?php
$_SERVER['SERVER_ADDR'] = ':ip';
$_SERVER['SERVER_PORT'] = ':port';
$_SERVER['REQUEST_SCHEME'] = 'http';
chdir('public');
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($uri !== '/') {
    if (file_exists('public/' . $uri)
        || preg_match('#\.(?:css|js|gif|png|jpg|jpeg|ttf|woff|ico)$#', $uri) === 1
    ) {
        return false;
    }
}

$_GET['_url'] = $uri;
$_REQUEST['_url'] = $uri;
require_once  'index.php';
STR;

        if ($value = $this->request->getValue(0)) {
            if (str_contains($value, ':')) {
                list($ip, $port) = explode(':', $value, 2);
            } elseif (is_numeric($value)) {
                $port = (int)$value;
            } else {
                $ip = $value;
            }
        }

        if (!$port) {
            $port = 9501;
            foreach ($this->config->get('dependencies') as $name => $config) {
                if (isset($config['port']) && str_ends_with($name, 'Server')) {
                    $port = $config['port'];
                    break;
                }
            }
        }

        $router = 'builtin_server_router.php';
        LocalFS::filePut("@tmp/$router", strtr($router_str, [':ip' => $ip, ':port' => $port]));

        console_log('info', "server listen on: $ip:$port");

        $prefix = $this->router->getPrefix();
        console_log('info', "http://127.0.0.1:$port" . ($prefix ?: '/'));
        if (DIRECTORY_SEPARATOR === '\\') {
            shell_exec("explorer.exe http://127.0.0.1:$port" . $prefix);
        }

        shell_exec("php -S $ip:$port -t public tmp/$router");
    }
}