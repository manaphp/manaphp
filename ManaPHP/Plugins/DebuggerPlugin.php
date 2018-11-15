<?php

namespace ManaPHP\Plugins;

use ManaPHP\Exception\HttpStatusException;
use ManaPHP\Logger\Log;
use ManaPHP\Plugin;
use ManaPHP\Component;
use ManaPHP\Version;

/**
 * Class ManaPHP\Plugins\DebuggerPlugin
 *
 * @package plugins
 *
 * @property-read \ManaPHP\RouterInterface        $router
 * @property-read \ManaPHP\UrlInterface           $url
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\RendererInterface      $renderer
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class DebuggerPlugin extends Plugin
{
    /**
     * @var string
     */
    protected $_template = 'Default';

    /**
     * @var string
     */
    protected $_file;

    protected $_view = [];

    protected $_log = [];

    protected $_sql_prepared = [];
    protected $_sql_executed = [];
    protected $_sql_count = 0;

    protected $_mongodb = [];

    protected $_events = [];

    public function init()
    {
        $this->eventsManager->peekEvent('*', [$this, '_eventHandlerPeek']);

        $this->attachEvent('router:beforeRoute');
    }

    public function saveInstanceState()
    {
        return true;
    }

    public function restoreInstanceState($data)
    {
        $this->save();

        $this->_file = null;
        $this->_view = [];
        $this->_log = [];
        $this->_sql_prepared = [];
        $this->_sql_executed = [];
        $this->_sql_count = 0;
        $this->_mongodb = [];
        $this->_events = [];
    }

    /**
     * @param string                      $event
     * @param \ManaPHP\ComponentInterface $source
     * @param mixed                       $data
     *
     * @return void
     */
    public function _eventHandlerPeek($event, $source, $data)
    {
        $this->_events[] = $event;

        if ($event === 'logger:log') {
            /**
             * @var Log $log
             */
            $log = $data;
            $format = '[%time%][%level%] %message%';
            $micro_date = explode(' ', microtime());
            $replaces = [
                '%time%' => date('H:i:s.', $micro_date[1]) . str_pad(ceil($micro_date[0] * 10000), '0', STR_PAD_LEFT),
                '%level%' => $log->level,
                '%message%' => $log->message
            ];
            $this->_log[] = [
                'level' => $log->level,
                'message' => strtr($format, $replaces)
            ];
        } elseif ($event === 'db:beforeQuery' || $event === 'db:beforeExecute') {
            /**
             * @var \ManaPHP\DbInterface $source
             */
            $preparedSQL = $source->getSQL();
            if (!isset($this->_sql_prepared[$preparedSQL])) {
                $this->_sql_prepared[$preparedSQL] = 1;
            } else {
                $this->_sql_prepared[$preparedSQL]++;
            }

            $this->_sql_count++;
            $this->_sql_executed[] = [
                'prepared' => $source->getSQL(),
                'bind' => $source->getBind(),
                'emulated' => $source->getEmulatedSQL()
            ];
        } elseif ($event === 'db:afterQuery' || $event === 'db:afterExecute') {
            /**
             * @var \ManaPHP\DbInterface $source
             */
            $this->_sql_executed[$this->_sql_count - 1]['elapsed'] = $data['elapsed'];
            $this->_sql_executed[$this->_sql_count - 1]['row_count'] = $source->affectedRows();
        } elseif ($event === 'db:beginTransaction' || $event === 'db:rollbackTransaction' || $event === 'db:commitTransaction') {
            $this->_sql_count++;

            $parts = explode(':', $event);
            $name = $parts[1];

            $this->_sql_executed[] = [
                'prepared' => $name,
                'bind' => [],
                'emulated' => $name,
                'time' => 0,
                'row_count' => 0
            ];

            $preparedSQL = $name;
            if (!isset($this->_sql_prepared[$preparedSQL])) {
                $this->_sql_prepared[$preparedSQL] = 1;
            } else {
                $this->_sql_prepared[$preparedSQL]++;
            }
        } elseif ($event === 'renderer:beforeRender') {
            $vars = $data['vars'];
            foreach ((array)$vars as $k => $v) {
                if ($v instanceof Component) {
                    unset($vars[$k]);
                }
            }
            unset($vars['di']);
            $this->_view[] = ['file' => $data['file'], 'vars' => $vars, 'base_name' => basename(dirname($data['file'])) . '/' . basename($data['file'])];
        } elseif ($event === 'mongodb:afterQuery') {
            $item = [];
            $item['type'] = 'query';
            $item['raw'] = ['namespace' => $data['namespace'], 'filter' => $data['filter'], 'options' => $data['options']];
            $options = $data['options'];
            list(, $collection) = explode('.', $data['namespace'], 2);
            $shell = "db.$collection.";
            $shell .= (isset($options['limit']) ? 'findOne(' : 'find(') . json_encode($data['filter'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (isset($options['projection'])) {
                $shell .= ', ' . json_encode($options['projection'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ');';
            } else {
                $shell .= ');';
            }

            $item['shell'] = $shell;
            $item['elapsed'] = $data['elapsed'];
            $this->_mongodb[] = $item;
        } elseif ($event === 'mongodb:afterCommand') {
            $item = [];
            $item['type'] = 'command';
            $item['raw'] = ['db' => $data['db'], 'command' => $data['command'], 'options' => $data['options']];
            $item['shell'] = [];
            $item['elapsed'] = $data['elapsed'];
            $this->_mongodb[] = $item;
        } elseif ($event === 'mongodb:afterBulkWrite') {
            $item = [];
            $item['type'] = 'bulkWrite';
            $item['raw'] = ['db' => $data['db'], 'command' => $data['command'], 'options' => $data['options']];
            $item['shell'] = [];
            $item['elapsed'] = $data['elapsed'];
            $this->_mongodb = [];
        }
    }

    public function onRouterBeforeRoute()
    {
        if (isset($_GET['_debugger']) && preg_match('#^[a-zA-Z0-9_/]+\.html$#', $_GET['_debugger'])) {
            $file = '@data/debugger' . $_GET['_debugger'];
            if ($this->filesystem->fileExists($file)) {
                $this->response->setContent($this->filesystem->fileGet($file));
                throw new HttpStatusException(200);
            }
        }

        return null;
    }

    /**
     * @return array
     */
    protected function _getBasic()
    {
        $loaded_extensions = get_loaded_extensions();
        sort($loaded_extensions, SORT_STRING | SORT_FLAG_CASE);
        $r = [
            'mvc' => $this->router->getController() . '::' . $this->router->getAction(),
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_url' => $this->request->getUrl(),
            'query_count' => $this->_sql_count,
            'execute_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4),
            'memory_usage' => (int)(memory_get_usage(true) / 1024) . 'k/' . (int)(memory_get_peak_usage(true) / 1024) . 'k',
            'system_time' => date('Y-m-d H:i:s'),
            'server_ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '',
            'client_ip' => $_SERVER['REMOTE_ADDR'],
            'operating_system' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
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
        $data['logger'] = ['log' => $this->_log, 'levels' => array_flip($this->logger->getLevels()), 'level' => 6];
        $data['sql'] = ['prepared' => $this->_sql_prepared, 'executed' => $this->_sql_executed, 'count' => $this->_sql_count];
        $data['mongodb'] = $this->_mongodb;

        $configure = isset($this->configure) ? $this->configure->__debugInfo() : [];
        unset($configure['alias']);
        $data['configure'] = $configure;

        $data['view'] = $this->_view;
        $data['components'] = [];
        $data['events'] = $this->_events;

        /** @noinspection ForeachSourceInspection */
        foreach ($this->_di->__debugInfo()['_instances'] as $k => $v) {
            if ($k === 'configure' || $k === 'debuggerPlugin') {
                continue;
            }

            $properties = $v instanceof Component ? $v->dump() : '';

            if ($k === 'response' && isset($properties['_content'])) {
                $properties['_content'] = '******[' . strlen($properties['_content']) . ']';
            }

            if ($k === 'renderer') {
                $properties['_sections'] = array_keys($properties['_sections']);
            }

            $data['components'][] = ['name' => $k, 'class' => get_class($v), 'properties' => $properties];
        }

        $template = strpos($this->_template, '/') !== false ? $this->_template : ('@manaphp/Plugins/DebuggerPlugin/Template/' . $this->_template);

        return $this->renderer->render($template, ['data' => $data], false);
    }

    /**
     */
    public function save()
    {
        if ($this->_file !== null) {
            $this->logger->debug('debugger-link: ' . $this->getUrl(), 'debugger.link');
            $this->filesystem->filePut('@data/debugger/' . $this->_file, $this->output());
        }
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if ($this->_file === null) {
            $this->_file = date('/ymd/His_') . $this->random->getBase(32) . '.html';
        }

        return $this->router->createUrl('/?_debugger=' . $this->_file, true);
    }

    public function __destruct()
    {
        $this->save();
    }
}