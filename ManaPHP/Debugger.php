<?php

namespace ManaPHP;

use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Debugger
 *
 * @package debugger
 *
 * @property \ManaPHP\Mvc\RouterInterface      $router
 * @property \ManaPHP\Mvc\UrlInterface         $url
 * @property \ManaPHP\Http\RequestInterface    $request
 * @property \ManaPHP\LoggerInterface          $logger
 * @property \ManaPHP\RendererInterface        $renderer
 * @property \ManaPHP\Security\RandomInterface $random
 * @property \ManaPHP\Http\ResponseInterface   $response
 */
class Debugger extends Component implements DebuggerInterface
{
    /**
     * @var string
     */
    protected $_template = 'Default';

    /**
     * @var string
     */
    protected $_file;

    protected $_dump = [];
    protected $_view = [];

    protected $_log = [];
    protected $_log_max = 512;

    protected $_sql_prepared = [];
    protected $_sql_prepared_max = 256;
    protected $_sql_executed = [];
    protected $_sql_executed_max = 256;

    protected $_sql_count = 0;
    protected $_sql_beforeQueryTime;

    protected $_exception = [];

    protected $_warnings = [];

    protected $_events = [];

    /**
     * Debugger constructor.
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['file' => $options];
        }

        if (!isset($options['file'])) {
            $options['file'] = date('/ymd/His_') . $this->random->getBase(32) . '.html';
        }

        $this->_file = $options['file'];

        $handler = [$this, '_eventHandlerPeek'];
        $this->eventsManager->peekEvents($handler);

        $this->attachEvent('dispatcher:beforeDispatch');
    }

    public function reConstruct()
    {
        $this->save();

        $this->_file = null;
        $this->_dump = [];
        $this->_view = [];
        $this->_log = [];
        $this->_sql_prepared = [];
        $this->_sql_executed = [];
        $this->_sql_count = 0;
        $this->_sql_beforeQueryTime = null;
        $this->_exception = [];
        $this->_warnings = [];
        $this->_events = [];
    }

    /**
     * @param \ManaPHP\ComponentInterface $source
     * @param mixed                       $data
     * @param string                      $event
     *
     * @return void
     */
    public function _eventHandlerPeek($source, $data, $event)
    {
        $this->_events[] = $event;

        if ($event === 'logger:log') {
            if (count($this->_log) <= $this->_log_max) {
                $format = '[%time%][%level%] %message%';
                $micro_date = explode(' ', microtime());
                $replaces = [
                    '%time%' => date('H:i:s.', $micro_date[1]) . str_pad(ceil($micro_date[0] * 10000), '0', STR_PAD_LEFT),
                    '%level%' => $data['level'],
                    '%message%' => $data['message']
                ];
                $this->_log[] = [
                    'level' => $data['level'],
                    'message' => strtr($format, $replaces)
                ];
            }
        } elseif ($event === 'db:beforeQuery') {
            $this->_sql_beforeQueryTime = microtime(true);
            /**
             * @var \ManaPHP\DbInterface $source
             */
            if (count($this->_sql_prepared) <= $this->_sql_prepared_max) {
                $preparedSQL = $source->getSQL();
                if (!isset($this->_sql_prepared[$preparedSQL])) {
                    $this->_sql_prepared[$preparedSQL] = 1;
                } else {
                    $this->_sql_prepared[$preparedSQL]++;
                }
            }

            $this->_sql_count++;
            if (count($this->_sql_executed) <= $this->_sql_executed_max) {
                $this->_sql_executed[] = [
                    'prepared' => $source->getSQL(),
                    'bind' => $source->getBind(),
                    'emulated' => $source->getEmulatedSQL()
                ];
            }
        } elseif ($event === 'db:afterQuery') {
            /**
             * @var \ManaPHP\DbInterface $source
             */
            if (count($this->_sql_executed) <= $this->_sql_executed_max) {
                $this->_sql_executed[$this->_sql_count - 1]['time'] = round(microtime(true) - $this->_sql_beforeQueryTime, 4);
                $this->_sql_executed[$this->_sql_count - 1]['row_count'] = $source->affectedRows();
            }
        } elseif ($event === 'db:beginTransaction' || $event === 'db:rollbackTransaction' || $event === 'db:commitTransaction') {
            $this->_sql_count++;

            $parts = explode(':', $event);
            $name = $parts[1];

            if (count($this->_sql_executed) <= $this->_sql_executed_max) {
                $this->_sql_executed[] = [
                    'prepared' => $name,
                    'bind' => [],
                    'emulated' => $name,
                    'time' => 0,
                    'row_count' => 0
                ];
            }

            if (count($this->_sql_prepared) <= $this->_sql_prepared_max) {
                $preparedSQL = $name;
                if (!isset($this->_sql_prepared[$preparedSQL])) {
                    $this->_sql_prepared[$preparedSQL] = 1;
                } else {
                    $this->_sql_prepared[$preparedSQL]++;
                }
            }
        } elseif ($event === 'renderer:beforeRender') {
            $vars = $data['vars'];
            unset($vars['view'], $vars['request']);
            $this->_view[] = ['file' => $data['file'], 'vars' => $vars, 'base_name' => basename(dirname($data['file'])) . '/' . basename($data['file'])];
        } elseif ($event === 'component:setUndefinedProperty') {
            $this->_warnings[] = 'Set to undefined property `' . $data['name'] . '` of `' . $data['class'] . '`';
        }
    }

    public function onDispatcherBeforeDispatch()
    {
        if (isset($_GET['_debugger']) && preg_match('#^[a-zA-Z0-9_/]+\.html$#', $_GET['_debugger'])) {
            $file = '@data/debugger' . $_GET['_debugger'];
            if ($this->filesystem->fileExists($file)) {
                $this->response->setContent($this->filesystem->fileGet($file));
                return false;
            }
        }
    }

    /**
     * @return void
     */
    public function start()
    {

    }

    /**
     * @param \Exception $exception
     *
     * @return bool
     */
    public function onUncaughtException($exception)
    {
        for ($i = ob_get_level(); $i > 0; $i--) {
            ob_end_clean();
        }

        $callers = [];
        foreach (explode("\n", $exception->getTraceAsString()) as $v) {

            $parts = explode(' ', $v, 2);
            $call = $parts[1];
            if (!Text::contains($call, ':')) {
                $call .= ': ';
            }
            $parts = explode(': ', $call, 2);
            $callers[] = ['location' => $parts[0], 'revoke' => $parts[1]];
        }
        $this->_exception = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'callers' => $callers,
        ];

        echo $this->output();

        return true;
    }

    /**
     * @param mixed  $value
     * @param string $name
     *
     * @return static
     */
    public function var_dump($value, $name = null)
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $caller = isset($traces[1]) && $traces[1]['object'] instanceof $this ? $traces[1] : $traces[0];

        if ($name === null) {
            $lines = file($caller['file']);
            $str = $lines[$caller['line'] - 1];
            $match = null;
            if (preg_match('#->var_dump\((.*)\)\s*;#', $str, $match) === 1) {
                $name = $match[1];
            }
        }

        $this->_dump[] = [
            'name' => $name,
            'value' => $value,
            'file' => strtr($caller['file'], '\\', '/'),
            'line' => $caller['line'],
            'base_name' => basename($caller['file'])
        ];

        return $this;
    }

    /**
     * @return array
     */
    protected function _getBasic()
    {
        $loaded_extensions = get_loaded_extensions();
        sort($loaded_extensions, SORT_STRING | SORT_FLAG_CASE);
        $r = [
            'mvc' => $this->router->getModuleName() . '::' . $this->router->getControllerName() . '::' . $this->router->getActionName(),
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_url' => $this->request->getUrl(),
            'query_count' => $this->_sql_count,
            'execute_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4),
            'memory_usage' => (int)(memory_get_usage(true) / 1024) . 'k/' . (int)(memory_get_peak_usage(true) / 1024) . 'k',
            'system_time' => date('Y-m-d H:i:s'),
            'server_ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '',
            'client_ip' => $_SERVER['REMOTE_ADDR'],
            'operating_system' => php_uname(),
            'manaphp_version' => Version::get(),
            'php_version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'loaded_ini' => php_ini_loaded_file(),
            'loaded_extensions' => implode(', ', $loaded_extensions)
        ];

        return $r;
    }

    /**
     * @return string
     */
    public function output()
    {
        $data = [];
        $data['basic'] = $this->_getBasic();

        $data['dump'] = $this->_dump;
        $data['logger'] = ['log' => $this->_log, 'levels' => $this->logger->getLevels(), 'level' => 6];

        $data['sql'] = ['prepared' => $this->_sql_prepared, 'executed' => $this->_sql_executed, 'count' => $this->_sql_count];

        $data['configure'] = isset($this->configure) ? $this->configure->__debugInfo() : [];
        $data['view'] = $this->_view;
        $data['exception'] = $this->_exception;
        $data['warnings'] = $this->_warnings;
        $data['components'] = [];
        $data['events'] = $this->_events;

        /** @noinspection ForeachSourceInspection */
        foreach ($this->_dependencyInjector->__debugInfo()['_sharedInstances'] as $k => $v) {
            if ($k === 'configure') {
                continue;
            }

            $properties = $v instanceof Component ? $v->dump() : '';

            if ($k === 'response') {
                $properties['_content'] = '******[' . strlen($properties['_content']) . ']';
            }

            $data['components'][] = ['name' => $k, 'class' => get_class($v), 'properties' => $properties];
        }

        $template = Text::contains($this->_template, '/') ? $this->_template : ('@manaphp/Debugger/Template/' . $this->_template);

        return $this->renderer->render($this->alias->resolve($template), ['data' => $data], false);
    }

    /**
     * @throws \ManaPHP\Debugger\Exception
     */
    public function save()
    {
        if ($this->_file !== null) {
            $this->filesystem->filePut('@data/debugger/' . $this->_file, $this->output());
        }
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url->get('/?_debugger=' . $this->_file);
    }

    public function __destruct()
    {
        $this->save();
    }
}