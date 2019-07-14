<?php

namespace ManaPHP\Plugins;

use ManaPHP\Component;
use ManaPHP\Exception\AbortException;
use ManaPHP\Logger;
use ManaPHP\Logger\Log;
use ManaPHP\Plugin;
use ManaPHP\Version;

class DebuggerPluginContext
{
    /**
     * @var string
     */
    public $file;

    public $view = [];

    public $log = [];

    public $sql_prepared = [];
    public $sql_executed = [];
    public $sql_count = 0;

    public $mongodb = [];

    public $events = [];
}

/**
 * Class DebuggerPlugin
 * @package ManaPHP\Plugins
 * @property \ManaPHP\Plugins\DebuggerPluginContext $_context
 */
class DebuggerPlugin extends Plugin
{
    /**
     * @var string
     */
    protected $_template = 'Default';

    public function __construct()
    {
        $this->eventsManager->peekEvent([$this, 'onEvent']);

        $this->eventsManager->attachEvent('request:begin', [$this, 'onRequestBegin']);
        $this->eventsManager->attachEvent('request:end', [$this, 'onRequestEnd']);
    }

    public function onRequestBegin()
    {
        if (preg_match('#^[a-zA-Z0-9_/]+\.html$#', $debugger = $this->request->get('_debugger', ''))) {
            $file = '@data/debugger' . $debugger;
            if ($this->filesystem->fileExists($file)) {
                $this->response->setContent($this->filesystem->fileGet($file));
                throw new AbortException();
            }
        }

        $context = $this->_context;
        $context->file = date('/ymd/His_') . $this->random->getBase(32) . '.html';
    }

    public function onRequestEnd()
    {
        $context = $this->_context;

        if ($context->file) {
            $this->filesystem->filePut('@data/debugger/' . $context->file, $this->output());
            $this->logger->info('debugger-link: `' . $this->getUrl() . '`', 'debugger.link');
        }
    }

    /**
     * @param string                      $event
     * @param \ManaPHP\ComponentInterface $source
     * @param mixed                       $data
     *
     * @return void
     */
    public function onEvent($event, $source, $data)
    {
        $context = $this->_context;

        $context->events[] = $event;

        if ($event === 'logger:log') {
            /**
             * @var Log $log
             */
            $log = $data;
            $format = '[%time%][%level%] %message%';
            $replaces = [
                '%time%' => date('H:i:s.', $log->timestamp) . sprintf('%.03d', ($log->timestamp - (int)$log->timestamp) * 1000),
                '%level%' => $log->level,
                '%message%' => $log->message
            ];
            $context->log[] = [
                'level' => $log->level,
                'message' => strtr($format, $replaces)
            ];
        } elseif ($event === 'db:beforeQuery' || $event === 'db:beforeExecute') {
            /**
             * @var \ManaPHP\DbInterface $source
             */
            $preparedSQL = $source->getSQL();
            if (!isset($context->sql_prepared[$preparedSQL])) {
                $context->sql_prepared[$preparedSQL] = 1;
            } else {
                $context->sql_prepared[$preparedSQL]++;
            }

            $context->sql_count++;
            $context->sql_executed[] = [
                'prepared' => $source->getSQL(),
                'bind' => $source->getBind(),
                'emulated' => $source->getEmulatedSQL()
            ];
        } elseif ($event === 'db:afterQuery' || $event === 'db:afterExecute') {
            /**
             * @var \ManaPHP\DbInterface $source
             */
            $context->sql_executed[$context->sql_count - 1]['elapsed'] = $data['elapsed'];
            $context->sql_executed[$context->sql_count - 1]['row_count'] = $source->affectedRows();
        } elseif ($event === 'db:begin' || $event === 'db:rollback' || $event === 'db:commit') {
            $context->sql_count++;

            $parts = explode(':', $event);
            $name = $parts[1];

            $context->sql_executed[] = [
                'prepared' => $name,
                'bind' => [],
                'emulated' => $name,
                'time' => 0,
                'row_count' => 0
            ];

            $preparedSQL = $name;
            if (!isset($context->_sql_prepared[$preparedSQL])) {
                $context->sql_prepared[$preparedSQL] = 1;
            } else {
                $context->sql_prepared[$preparedSQL]++;
            }
        } elseif ($event === 'renderer:beforeRender') {
            $vars = $data['vars'];
            foreach ((array)$vars as $k => $v) {
                if ($v instanceof Component) {
                    unset($vars[$k]);
                }
            }
            unset($vars['di']);
            $context->view[] = ['file' => $data['file'], 'vars' => $vars, 'base_name' => basename(dirname($data['file'])) . '/' . basename($data['file'])];
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
            $context->mongodb[] = $item;
        } elseif ($event === 'mongodb:afterCommand') {
            $item = [];
            $item['type'] = 'command';
            $item['raw'] = ['db' => $data['db'], 'command' => $data['command'], 'options' => $data['options']];
            $item['shell'] = [];
            $item['elapsed'] = $data['elapsed'];
            $context->mongodb[] = $item;
        } elseif ($event === 'mongodb:afterBulkWrite') {
            $item = [];
            $item['type'] = 'bulkWrite';
            $item['raw'] = ['db' => $data['db'], 'command' => $data['command'], 'options' => $data['options']];
            $item['shell'] = [];
            $item['elapsed'] = $data['elapsed'];
            $context->mongodb = [];
        }
    }

    /**
     * @return array
     */
    protected function _getBasic()
    {
        $context = $this->_context;

        $loaded_extensions = get_loaded_extensions();
        sort($loaded_extensions, SORT_STRING | SORT_FLAG_CASE);
        $r = [
            'mvc' => $this->router->getController() . '::' . $this->router->getAction(),
            'request_method' => $this->request->getServer('REQUEST_METHOD'),
            'request_url' => $this->request->getUrl(),
            'query_count' => $context->sql_count,
            'execute_time' => round(microtime(true) - $this->request->getServer('REQUEST_TIME_FLOAT'), 4),
            'memory_usage' => (int)(memory_get_usage(true) / 1024) . 'k/' . (int)(memory_get_peak_usage(true) / 1024) . 'k',
            'system_time' => date('Y-m-d H:i:s'),
            'server_ip' => $this->request->getServer('SERVER_ADDR'),
            'client_ip' => $this->request->getClientIp(),
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
        $context = $this->_context;

        $data = [];
        $data['basic'] = $this->_getBasic();
        $data['logger'] = ['log' => $context->log, 'levels' => array_flip($this->logger->getLevels()), 'level' => Logger::LEVEL_DEBUG];
        $data['sql'] = ['prepared' => $context->sql_prepared, 'executed' => $context->sql_executed, 'count' => $context->sql_count];
        $data['mongodb'] = $context->mongodb;

        $configure = isset($this->configure) ? $this->configure->__debugInfo() : [];
        unset($configure['alias']);
        $data['configure'] = $configure;

        $data['view'] = $context->view;
        $data['components'] = [];
        $data['events'] = $context->events;

        /** @noinspection ForeachSourceInspection */
        foreach ($this->_di->__debugInfo()['_instances'] as $k => $v) {
            if ($k === 'configure' || $k === 'debuggerPlugin') {
                continue;
            }

            $properties = $v instanceof Component ? $v->__debugInfo() : [];

            foreach ($properties as $pk => $pv) {
                if ($pv instanceof Component || $pk === 'eventsManager') {
                    unset($properties[$pk]);
                }
            }

            $data['components'][] = ['name' => $k, 'class' => get_class($v), 'properties' => $properties];
        }

        $template = strpos($this->_template, '/') !== false ? $this->_template : ('@manaphp/Plugins/DebuggerPlugin/Template/' . $this->_template);

        return $this->renderer->render($template, ['data' => $data]);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $context = $this->_context;

        return $this->router->createUrl('/?_debugger=' . $context->file, true);
    }
}