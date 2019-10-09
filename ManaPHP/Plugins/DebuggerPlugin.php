<?php

namespace ManaPHP\Plugins;

use ManaPHP\Component;
use ManaPHP\Exception\AbortException;
use ManaPHP\Logger;
use ManaPHP\Plugin;
use ManaPHP\Version;

class DebuggerPluginContext
{
    /**
     * @var bool
     */
    public $enabled;

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
 * @property-read \ManaPHP\Plugins\DebuggerPluginContext $_context
 */
class DebuggerPlugin extends Plugin
{
    /**
     * @var string
     */
    protected $_template = '@manaphp/Plugins/DebuggerPlugin/Template.html';

    /**
     * DebuggerPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['template'])) {
            $this->_template = $options['template'];
        }

        $this->eventsManager->peekEvent('db', [$this, 'onDb']);
        $this->eventsManager->peekEvent('mongodb', [$this, 'onMongodb']);

        $this->eventsManager->attachEvent('renderer:rendering', [$this, 'onRendererRendering']);
        $this->eventsManager->attachEvent('logger:log', [$this, 'onLoggerLog']);
        $this->eventsManager->attachEvent('request:begin', [$this, 'onRequestBegin']);
        $this->eventsManager->attachEvent('response:sending', [$this, 'onResponseSending']);
        $this->eventsManager->attachEvent('request:end', [$this, 'onRequestEnd']);
    }

    public function onRequestBegin()
    {
        $context = $this->_context;

        if (($debugger = $this->request->get('__debuggerPlugin', '')) && preg_match('#^([\w/]+)\.(html|json)$#', $debugger, $match)) {
            $context->enabled = false;
            $file = '@data/debugger' . $match[1] . '.json';
            if ($this->filesystem->fileExists($file)) {
                $ext = $match[2];
                $json = $this->filesystem->fileGet($file);
                if ($ext === 'html') {
                    $this->response->setContent(strtr($this->filesystem->fileGet($this->_template), ['DEBUGGER_DATA' => $json]));
                } else {
                    $this->response->setJsonContent($json);
                }
            } else {
                $this->response->setContent('NOT FOUND')->setStatus(404);
            }
			
            throw new AbortException();
        } elseif (strpos($this->request->getServer('HTTP_USER_AGENT'), 'ApacheBench') !== false) {
            $context->enabled = false;
        } else {
            $context->enabled = true;
            $context->file = date('/ymd/His_') . $this->random->getBase(32);
        }
    }

    public function onResponseSending()
    {
        if ($this->_context->enabled) {
            $this->response->setHeader('X-Debugger-Link', $this->getUrl());
        }
    }

    public function onRequestEnd()
    {
        $context = $this->_context;

        if ($context->enabled) {
            $this->save('@data/debugger/' . $context->file . '.json');
            $this->logger->info('debugger-link: `' . $this->getUrl() . '`', 'debugger.link');
        }
    }

    /**
     * @param \ManaPHP\LoggerInterface $logger
     * @param \ManaPHP\Logger\Log      $log
     */
    public function onLoggerLog($logger, $log)
    {
        $context = $this->_context;

        $context->log[] = [
            'time' => date('H:i:s.', $log->timestamp) . sprintf('%.03d', ($log->timestamp - (int)$log->timestamp) * 1000),
            'level' => $log->level,
            'category' => $log->category,
            'file' => $log->file,
            'line' => $log->line,
            'message' => $log->message
        ];
    }

    /**
     * @param string               $event
     * @param \ManaPHP\DbInterface $db
     * @param array                $data
     */
    public function onDb($event, $db, $data)
    {
        $context = $this->_context;

        if ($event === 'db:querying' || $event === 'db:executing') {
            $preparedSQL = $db->getSQL();
            if (!isset($context->sql_prepared[$preparedSQL])) {
                $context->sql_prepared[$preparedSQL] = 1;
            } else {
                $context->sql_prepared[$preparedSQL]++;
            }

            $context->sql_count++;
            $context->sql_executed[] = [
                'prepared' => $db->getSQL(),
                'bind' => $db->getBind(),
                'emulated' => $db->getEmulatedSQL()
            ];
        } elseif ($event === 'db:queried' || $event === 'db:executed') {
            /** @var \ManaPHP\DbInterface $source */
            $context->sql_executed[$context->sql_count - 1]['elapsed'] = $data['elapsed'];
            $context->sql_executed[$context->sql_count - 1]['row_count'] = $db->affectedRows();
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
        }
    }

    /**
     * @param \ManaPHP\RendererInterface $renderer
     * @param array[]                    $data
     */
    public function onRendererRendering($renderer, $data)
    {
        $context = $this->_context;

        $vars = $data['vars'];
        foreach ((array)$vars as $k => $v) {
            if ($v instanceof Component) {
                unset($vars[$k]);
            }
        }
        unset($vars['di']);
        $context->view[] = ['file' => $data['file'], 'vars' => $vars, 'base_name' => basename(dirname($data['file'])) . '/' . basename($data['file'])];
    }

    /**
     * @param string                    $event
     * @param \ManaPHP\MongodbInterface $mongodb
     * @param array                     $data
     */
    public function onMongodb($event, $mongodb, $data)
    {
        $context = $this->_context;
        if ($event === 'mongodb:queried') {
            $item = [];
            $item['type'] = 'query';
            $item['raw'] = ['namespace' => $data['namespace'], 'filter' => $data['filter'], 'options' => $data['options']];
            $options = $data['options'];
            list(, $collection) = explode('.', $data['namespace'], 2);
            $shell = "db.$collection.";
            $shell .= (isset($options['limit']) ? 'findOne(' : 'find(') . json_stringify($data['filter']);
            if (isset($options['projection'])) {
                $shell .= ', ' . json_stringify($options['projection']) . ');';
            } else {
                $shell .= ');';
            }

            $item['shell'] = $shell;
            $item['elapsed'] = $data['elapsed'];
            $context->mongodb[] = $item;
        } elseif ($event === 'mongodb:commanded') {
            $item = [];
            $item['type'] = 'command';
            $item['raw'] = ['db' => $data['db'], 'command' => $data['command'], 'options' => $data['options']];
            $item['shell'] = [];
            $item['elapsed'] = $data['elapsed'];
            $context->mongodb[] = $item;
        } elseif ($event === 'mongodb:bulkWritten') {
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
            'operating_system' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'manaphp_version' => Version::get(),
            'php_version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'loaded_ini' => php_ini_loaded_file(),
            'loaded_extensions' => implode(', ', $loaded_extensions)
        ];

        return $r;
    }

    /**
     * @param string $file
     *
     * @return void
     */
    public function save($file)
    {
        $context = $this->_context;

        $data = [];
        $data['basic'] = $this->_getBasic();
        $data['logger'] = ['log' => $context->log, 'levels' => array_flip($this->logger->getLevels()), 'level' => Logger::LEVEL_DEBUG];
        $data['sql'] = ['prepared' => $context->sql_prepared, 'executed' => $context->sql_executed, 'count' => $context->sql_count];
        $data['mongodb'] = $context->mongodb;

        $data['view'] = $context->view;
        $data['components'] = [];
        $data['events'] = $context->events;

        foreach ($this->_di->getInstances() as $name => $instance) {
            if (strpos($name, '\\') !== false) {
                continue;
            }

            $properties = $instance instanceof Component ? $instance->dump() : array_keys(get_object_vars($instance));
            $data['components'][$name] = ['class' => get_class($instance), 'properties' => $properties];
        }

        $data['included_files'] = @get_included_files() ?: [];
        unset($data['server']['PATH']);

        $this->filesystem->filePut($file, json_stringify($data, JSON_PRETTY_PRINT));
    }

    public function dump()
    {
        $data = parent::dump();

        $data['_context'] = array_keys($data['_context']);

        return $data;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $context = $this->_context;

        return $this->router->createUrl('/?__debuggerPlugin=' . $context->file . '.html', true);
    }
}