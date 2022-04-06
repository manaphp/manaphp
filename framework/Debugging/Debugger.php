<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

use ArrayObject;
use ManaPHP\Component;
use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ManaPHP\Logging\Level;
use ManaPHP\Tracer;
use ManaPHP\Version;

/**
 * @property-read \ManaPHP\Di\InspectorInterface             $inspector
 * @property-read \ManaPHP\ConfigInterface                   $config
 * @property-read \ManaPHP\Logging\LoggerInterface           $logger
 * @property-read \ManaPHP\Http\RequestInterface             $request
 * @property-read \ManaPHP\Http\ResponseInterface            $response
 * @property-read \ManaPHP\Http\DispatcherInterface          $dispatcher
 * @property-read \ManaPHP\Http\RouterInterface              $router
 * @property-read \ManaPHP\Data\RedisCacheInterface          $redisCache
 * @property-read \ManaPHP\Data\Db\PreparedEmulatorInterface $preparedEmulator
 * @property-read \ManaPHP\Debugging\DebuggerContext         $context
 */
class Debugger extends Component implements DebuggerInterface
{
    protected int $ttl;
    protected string $prefix;
    protected string $template;
    protected bool $broadcast;
    protected bool $tail;

    public function __construct(int $ttl = 3600, ?string $prefix = null,
        string $template = '@manaphp/Debugging/Debugger/Template.html',
        bool $broadcast = true, bool $tail = true
    ) {
        $this->ttl = class_exists('Redis') ? $ttl : 0;
        $this->prefix = $prefix ?? sprintf("cache:%s:debugger:", $this->config->get('id'));
        $this->template = $template;
        $this->broadcast = $broadcast;
        $this->tail = $tail;
    }

    public function start(): void
    {
        $this->eventManager->peekEvent('*', [$this, 'onEvent']);

        $this->eventManager->peekEvent('db', [$this, 'onDb']);
        $this->eventManager->peekEvent('mongodb', [$this, 'onMongodb']);

        $this->attachEvent('renderer:rendering', [$this, 'onRendererRendering']);
        $this->attachEvent('logger:log', [$this, 'onLoggerLog']);
        $this->attachEvent('request:begin', [$this, 'onRequestBegin']);
        $this->attachEvent('request:end', [$this, 'onRequestEnd']);

        if ($this->tail) {
            $this->attachEvent('response:stringify', [$this, 'onResponseStringify']);
        }
    }

    protected function readData(string $key): false|string
    {
        if ($this->ttl) {
            $content = $this->redisCache->get($this->prefix . $key);
        } else {
            $file = "@runtime/debugger/{$key}.zip";
            $content = LocalFS::fileExists($file) ? LocalFS::fileGet($file) : false;
        }

        return is_string($content) ? gzdecode($content) : $content;
    }

    protected function writeData(string $key, array $data): void
    {
        $content = gzencode(json_stringify($data, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PRETTY_PRINT));
        if ($this->ttl) {
            $this->redisCache->set($this->prefix . $key, $content, $this->ttl);

            if ($this->broadcast) {
                $key = implode(
                    ':',
                    ['__debugger', $this->config->get('id'), $this->request->getClientIp(),
                     $this->dispatcher->getPath()]
                );
                $this->redisCache->publish($key, $this->response->getHeader('X-Debugger-Link'));
            }
        } else {
            LocalFS::filePut("@runtime/debugger/{$key}.zip", $content);
        }
    }

    public function onRequestBegin(): void
    {
        $context = $this->context;

        if (($debugger = $this->request->get('__debugger', ''))
            && preg_match('#^([\w/]+)\.(html|json|txt|raw)$#', $debugger, $match)
        ) {
            $context->enabled = false;
            if (($data = $this->readData($match[1])) !== false) {
                $ext = $match[2];
                if ($ext === 'html') {
                    $this->response->setContent(strtr(LocalFS::fileGet($this->template), ['DEBUGGER_DATA' => $data]));
                } elseif ($ext === 'txt') {
                    $this->response->setContent(json_stringify(json_parse($data), JSON_PRETTY_PRINT))
                        ->setContentType('text/plain;charset=UTF-8');
                } elseif ($ext === 'raw') {
                    $this->response->setContent($data)->setContentType('text/plain;charset=UTF-8');
                } else {
                    $this->response->setJsonContent($data);
                }
            } else {
                $this->response->setContent('NOT FOUND')->setStatus(404);
            }

            throw new AbortException();
        } elseif (str_contains($this->request->getUserAgent(), 'ApacheBench')) {
            $context->enabled = false;
        } else {
            $context->enabled = true;
            $context->key = date('/ymd/His_') . Str::random(32);
        }

        if ($context->enabled) {
            $url = $this->router->createUrl("/?__debugger={$context->key}.html", true);
            $this->response->setHeader('X-Debugger-Link', $url);
            $this->logger->info($url, 'debugger.link');
        }
    }

    public function onRequestEnd(): void
    {
        $context = $this->context;

        if ($context->enabled) {
            $this->writeData($context->key, $this->getData());
        }
    }

    public function onResponseStringify(): void
    {
        if (is_array($content = $this->response->getContent())) {
            $content['debugger'] = $this->response->getHeader('X-Debugger-Link');
            $this->response->setContent($content);
        }
    }

    public function onEvent(EventArgs $eventArgs): void
    {
        $event['event'] = $eventArgs->event;
        $event['source'] = $eventArgs->source ? get_class($eventArgs->source) : null;

        $data = $eventArgs->data;
        if ($data === null) {
            $event['data'] = null;
        } elseif (is_scalar($data)) {
            $event['data'] = gettype($data);
        } elseif ($data instanceof ArrayObject) {
            $event['data'] = array_keys($data->getArrayCopy());
        } elseif (is_array($data)) {
            $event['data'] = array_keys($data);
        } elseif (is_object($data)) {
            $event['data'] = get_class($data);
        } else {
            $event['data'] = '???';
        }

        $this->context->events[] = $event;
    }

    public function onLoggerLog(EventArgs $eventArgs): void
    {
        $context = $this->context;

        /** @var \ManaPHP\Logging\Logger\Log $log */
        $log = $eventArgs->data['log'];
        $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
        $context->log[] = [
            'time'     => date('H:i:s', (int)$log->timestamp) . $ms,
            'level'    => $log->level,
            'category' => $log->category,
            'file'     => $log->file,
            'line'     => $log->line,
            'message'  => $log->message
        ];
    }

    public function onDb(EventArgs $eventArgs): void
    {
        $context = $this->context;

        $event = $eventArgs->event;
        /** @var \ManaPHP\Data\DbInterface $db */
        $db = $eventArgs->source;

        if ($event === 'db:querying' || $event === 'db:executing') {
            $preparedSQL = $db->getSQL();
            if (!isset($context->sql_prepared[$preparedSQL])) {
                $context->sql_prepared[$preparedSQL] = 1;
            } else {
                $context->sql_prepared[$preparedSQL]++;
            }

            $context->sql_count++;

            $sql = $db->getSQL();
            $bind = $db->getBind();
            $context->sql_executed[] = [
                'prepared' => $sql,
                'bind'     => $bind,
                'emulated' => $this->preparedEmulator->emulate($sql, $bind, 128)
            ];
        } elseif ($event === 'db:queried' || $event === 'db:executed') {
            $context->sql_executed[$context->sql_count - 1]['elapsed'] = $eventArgs->data['elapsed'];
            $context->sql_executed[$context->sql_count - 1]['row_count'] = $db->affectedRows();
        } elseif ($event === 'db:begin' || $event === 'db:rollback' || $event === 'db:commit') {
            $context->sql_count++;

            $parts = explode(':', $event);
            $name = $parts[1];

            $context->sql_executed[] = [
                'prepared'  => $name,
                'bind'      => [],
                'emulated'  => $name,
                'time'      => 0,
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

    public function onRendererRendering(EventArgs $eventArgs): void
    {
        $context = $this->context;

        $vars = $eventArgs->data['vars'];
        foreach ((array)$vars as $k => $v) {
            if ($v instanceof Component) {
                unset($vars[$k]);
            }
        }

        $file = $eventArgs->data['file'];
        $base_name = basename(dirname($file)) . '/' . basename($file);
        $context->view[] = ['file' => $file, 'vars' => $vars, 'base_name' => $base_name];
    }

    public function onMongodb(EventArgs $eventArgs): void
    {
        $context = $this->context;

        $event = $eventArgs->event;
        $data = $eventArgs->data;

        if ($event === 'mongodb:queried') {
            $item = [];
            $item['type'] = 'query';
            $item['raw'] = Arr::only($data, ['namespace', 'filter', 'options']);
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
            $context->mongodb = $item;
        }
    }

    protected function getBasic(): array
    {
        $context = $this->context;

        $loaded_extensions = get_loaded_extensions();
        sort($loaded_extensions, SORT_STRING | SORT_FLAG_CASE);
        $memory_usage = (int)(memory_get_usage(true) / 1024) . 'k/' . (int)(memory_get_peak_usage(true) / 1024) . 'k';

        return [
            'mvc'                => $this->router->getController() . '::' . $this->router->getAction(),
            'request_method'     => $this->request->getMethod(),
            'request_url'        => $this->request->getUrl(),
            'query_count'        => $context->sql_count,
            'execute_time'       => $this->request->getElapsedTime(),
            'memory_usage'       => $memory_usage,
            'system_time'        => date('Y-m-d H:i:s'),
            'server_ip'          => $this->request->getServer('SERVER_ADDR'),
            'client_ip'          => $this->request->getClientIp(),
            'server_software'    => $this->request->getServer('SERVER_SOFTWARE'),
            'manaphp_version'    => Version::get(),
            'php_version'        => PHP_VERSION,
            'sapi'               => PHP_SAPI,
            'loaded_ini'         => php_ini_loaded_file(),
            'loaded_extensions'  => implode(', ', $loaded_extensions),
            'opcache.enable'     => ini_get('opcache.enable'),
            'opcache.enable_cli' => ini_get('opcache.enable_cli'),
        ];
    }

    protected function getData(): array
    {
        $context = $this->context;

        $data = [];
        $data['basic'] = $this->getBasic();
        $levels = Level::map();
        $data['logger'] = ['log' => $context->log, 'levels' => $levels, 'level' => $levels[Level::DEBUG]];
        $data['sql'] = [
            'prepared' => $context->sql_prepared,
            'executed' => $context->sql_executed,
            'count'    => $context->sql_count
        ];
        $data['mongodb'] = $context->mongodb;

        $data['view'] = $context->view;
        $data['dependencies'] = [];
        $data['tracers'] = [];
        $data['events'] = $context->events;

        foreach ($this->inspector->getInstances() as $name => $instance) {
            $properties = $instance instanceof Component
                ? $instance->dump()
                : array_keys(get_object_vars($instance));

            if ($instance instanceof Tracer) {
                $name = str_replace('\\', '//', $name);
                $data['tracers'][lcfirst(basename($name, 'Tracer'))] = ['class'      => get_class($instance),
                                                                        'properties' => $properties];
                continue;
            }

            $data['dependencies'][$name] = ['class'      => get_class($instance),
                                            'object_id'  => spl_object_id($instance),
                                            'properties' => $properties];
        }

        $data['included_files'] = @get_included_files() ?: [];
        unset($data['server']['PATH']);

        return $data;
    }

    public function dump(): array
    {
        $data = parent::dump();

        $data['context'] = array_keys($data['context']);

        return $data;
    }
}
