<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

use ManaPHP\ConfigInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Db\Event\DbBegin;
use ManaPHP\Db\Event\DbCommit;
use ManaPHP\Db\Event\DbExecuted;
use ManaPHP\Db\Event\DbExecuting;
use ManaPHP\Db\Event\DbQueried;
use ManaPHP\Db\Event\DbQuerying;
use ManaPHP\Db\Event\DbRollback;
use ManaPHP\Db\PreparedEmulatorInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Di\ContainerInterface;
use ManaPHP\Dumping\DumperManagerInterface;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\EventSubscriberInterface;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\Server\Event\RequestBegin;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Http\Server\Event\ResponseStringify;
use ManaPHP\Logging\Level;
use ManaPHP\Logging\Logger\Event\LoggerLog;
use ManaPHP\Logging\LoggerInterface;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Mongodb\Event\MongodbBulkWritten;
use ManaPHP\Mongodb\Event\MongodbCommanded;
use ManaPHP\Mongodb\Event\MongodbQueried;
use ManaPHP\Redis\RedisCacheInterface;
use ManaPHP\Rendering\Renderer\Event\RendererRendering;
use ManaPHP\Tracer;
use ManaPHP\Version;

class Debugger implements DebuggerInterface
{
    use ContextTrait;

    #[Inject] protected EventSubscriberInterface $eventSubscriber;
    #[Inject] protected ConfigInterface $config;
    #[Inject] protected LoggerInterface $logger;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;
    #[Inject] protected DispatcherInterface $dispatcher;
    #[Inject] protected RouterInterface $router;
    #[Inject] protected ContainerInterface $container;
    #[Inject] protected PreparedEmulatorInterface $preparedEmulator;
    #[Inject] protected DumperManagerInterface $dumpManager;

    protected int $ttl;
    protected string $prefix;
    #[Value] protected string $template = '@manaphp/Debugging/Debugger/Template.html';
    #[Value] protected bool $broadcast = true;
    #[Value] protected bool $tail = true;

    public function __construct(int $ttl = 3600, ?string $prefix = null)
    {
        $this->ttl = class_exists('Redis') ? $ttl : 0;
        $this->prefix = $prefix ?? sprintf("cache:%s:debugger:", $this->config->get('id'));
    }

    public function start(): void
    {
        $this->eventSubscriber->addListener($this);
    }

    protected function readData(string $key): ?string
    {
        $redisCache = $this->container->get(RedisCacheInterface::class);

        if ($this->ttl) {
            if (($content = $redisCache->get($this->prefix . $key)) === false) {
                return null;
            }
        } else {
            $file = "@runtime/debugger/{$key}.zip";
            $content = LocalFS::fileExists($file) ? LocalFS::fileGet($file) : null;
        }

        return is_string($content) ? gzdecode($content) : $content;
    }

    protected function writeData(string $key, array $data): void
    {
        $redisCache = $this->container->get(RedisCacheInterface::class);

        $content = gzencode(json_stringify($data, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PRETTY_PRINT));
        if ($this->ttl) {
            $redisCache->set($this->prefix . $key, $content, $this->ttl);

            if ($this->broadcast) {
                $key = implode(
                    ':',
                    ['__debugger', $this->config->get('id'), $this->request->getClientIp(),
                     $this->dispatcher->getPath()]
                );
                $redisCache->publish($key, $this->response->getHeader('X-Debugger-Link'));
            }
        } else {
            LocalFS::filePut("@runtime/debugger/{$key}.zip", $content);
        }
    }

    public function onRequestBegin(#[Event] RequestBegin $event): void
    {
        /** @var DebuggerContext $context */
        $context = $this->getContext();

        if (($debugger = $this->request->get('__debugger', ''))
            && preg_match('#^([\w/]+)\.(html|json|txt|raw)$#', $debugger, $match)
        ) {
            $context->enabled = false;
            if (($data = $this->readData($match[1])) !== null) {
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

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        /** @var DebuggerContext $context */
        $context = $this->getContext();

        if ($context->enabled) {
            $this->writeData($context->key, $this->getData());
        }
    }

    public function onResponseStringify(#[Event] ResponseStringify $event): void
    {
        if ($this->tail) {
            if (is_array($content = $this->response->getContent())) {
                $content['debugger'] = $this->response->getHeader('X-Debugger-Link');
                $this->response->setContent($content);
            }
        }
    }

    public function onEvent(#[Event] object $event): void
    {
        $data['event'] = $event::class;
        $data['source'] = array_keys(get_object_vars($event));

        /** @var DebuggerContext $context */
        $context = $this->getContext();

        $context->events[] = $data;
    }

    public function onLoggerLog(#[Event] LoggerLog $event): void
    {
        /** @var DebuggerContext $context */
        $context = $this->getContext();

        $log = $event->log;
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

    public function onDb(#[Event] object $event): void
    {
        /** @var DebuggerContext $context */
        $context = $this->getContext();

        if ($event instanceof DbQuerying || $event instanceof DbExecuting) {
            $preparedSQL = $event->db->getSQL();
            if (!isset($context->sql_prepared[$preparedSQL])) {
                $context->sql_prepared[$preparedSQL] = 1;
            } else {
                $context->sql_prepared[$preparedSQL]++;
            }

            $context->sql_count++;

            $sql = $event->db->getSQL();
            $bind = $event->db->getBind();
            $context->sql_executed[] = [
                'prepared' => $sql,
                'bind'     => $bind,
                'emulated' => $this->preparedEmulator->emulate($sql, $bind, 128)
            ];
        } elseif ($event instanceof DbQueried || $event instanceof DbExecuted) {
            $context->sql_executed[$context->sql_count - 1]['elapsed'] = $event->elapsed;
            $context->sql_executed[$context->sql_count - 1]['row_count'] = $event->db->affectedRows();
        } elseif ($event instanceof DbBegin || $event instanceof DbCommit || $event instanceof DbRollback) {
            $context->sql_count++;

            $name = $event::class;

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

    public function onRendererRendering(#[Event] RendererRendering $event): void
    {
        /** @var DebuggerContext $context */
        $context = $this->getContext();

        $vars = $event->vars;
        foreach ($vars as $k => $v) {
            if (is_object($v) && !$v instanceof ModelInterface && class_implements($v) !== []) {
                unset($vars[$k]);
            }
        }

        $file = $event->file;
        $base_name = basename(dirname($file)) . '/' . basename($file);
        $context->view[] = ['file' => $file, 'vars' => $vars, 'base_name' => $base_name];
    }

    public function onMongodb(#[Event] object $event): void
    {
        /** @var DebuggerContext $context */
        $context = $this->getContext();

        if ($event instanceof MongodbQueried) {
            $item = [];
            $item['type'] = 'query';
            $item['raw'] = Arr::only((array)$event, ['namespace', 'filter', 'options']);
            $options = $event->options;
            list(, $collection) = explode('.', $event->namespace, 2);
            $shell = "db.$collection.";
            $shell .= (isset($options['limit']) ? 'findOne(' : 'find(') . json_stringify($event->filter);
            if (isset($options['projection'])) {
                $shell .= ', ' . json_stringify($options['projection']) . ');';
            } else {
                $shell .= ');';
            }

            $item['shell'] = $shell;
            $item['elapsed'] = $event->elapsed;
            $context->mongodb[] = $item;
        } elseif ($event instanceof MongodbCommanded) {
            $item = [];
            $item['type'] = 'command';
            $item['raw'] = ['db' => $event->db, 'command' => $event->command];
            $item['shell'] = [];
            $item['elapsed'] = $event->elapsed;
            $context->mongodb[] = $item;
        } elseif ($event instanceof MongodbBulkWritten) {
            $item = [];
            $item['type'] = 'bulkWrite';
            $item['shell'] = [];
            $context->mongodb = $item;
        }
    }

    protected function getBasic(): array
    {
        /** @var DebuggerContext $context */
        $context = $this->getContext();

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
        /** @var DebuggerContext $context */
        $context = $this->getContext();

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

        foreach ($this->container->getInstances() as $name => $instance) {
            $properties = $this->dumpManager->dump($instance);

            if ($instance instanceof Tracer) {
                $name = str_replace('\\', '//', $name);
                $data['tracers'][lcfirst(basename($name, 'Tracer'))] = ['class'      => $instance::class,
                                                                        'properties' => $properties];
                continue;
            }

            $data['dependencies'][$name] = ['class'      => $instance::class,
                                            'object_id'  => spl_object_id($instance),
                                            'properties' => $properties];
        }

        $data['included_files'] = @get_included_files() ?: [];
        unset($data['server']['PATH']);

        return $data;
    }
}
