<?php

namespace ManaPHP\Logging;

use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ManaPHP\Plugin;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class LoggerPluginContext
{
    /**
     * @var bool
     */
    public $enabled = true;

    /**
     * @var string
     */
    public $key;

    /**
     * @var array
     */
    public $logs = [];
}

/**
 * @property-read \ManaPHP\Configuration\Configure     $configure
 * @property-read \ManaPHP\Logging\LoggerInterface     $logger
 * @property-read \ManaPHP\Http\RequestInterface       $request
 * @property-read \ManaPHP\Http\ResponseInterface      $response
 * @property-read \ManaPHP\Http\RouterInterface        $router
 * @property-read \Redis|\ManaPHP\Data\RedisInterface  $redisCache
 * @property-read \ManaPHP\Logging\LoggerPluginContext $context
 */
class LoggerPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var int
     */
    protected $ttl = 300;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $template = '@manaphp/Logging/LoggerPlugin/Template.html';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (MANAPHP_CLI) {
            $this->enabled = false;
        } elseif (isset($options['enabled'])) {
            $this->enabled = (bool)$options['enabled'];
        } elseif (!in_array($this->configure->env, ['dev', 'test'], true)) {
            $this->enabled = false;
        }

        if (isset($options['ttl'])) {
            $this->ttl = (int)$options['ttl'];
        }

        $this->prefix = $options['prefix'] ?? sprintf("cache:%s:loggerPlugin:", APP_ID);

        if (isset($options['template'])) {
            $this->template = $options['template'];
        }

        if ($this->enabled) {
            $this->attachEvent('request:begin', [$this, 'onRequestBegin']);
            $this->attachEvent('logger:log', [$this, 'onLoggerLog']);
            $this->attachEvent('request:end', [$this, 'onRequestEnd']);
        }
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    protected function readData($key)
    {
        if ($this->ttl) {
            $data = $this->redisCache->get($this->prefix . $key);
        } else {
            $file = "@data/loggerPlugin/{$key}.zip";
            $data = LocalFS::fileExists($file) ? LocalFS::fileGet($file) : false;
        }

        return is_string($data) ? gzdecode($data) : $data;
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @return void
     *
     * @throws \ManaPHP\Exception\JsonException
     */
    protected function writeData($key, $data)
    {
        $content = gzencode(json_stringify($data, JSON_PARTIAL_OUTPUT_ON_ERROR));
        if ($this->ttl) {
            $this->redisCache->set($this->prefix . $key, $content, $this->ttl);
        } else {
            LocalFS::filePut("@data/loggerPlugin/{$key}.zip", $content);
        }
    }

    /**
     * @return void
     */
    public function onRequestBegin()
    {
        $context = $this->context;

        if (($logger = $this->request->get('__loggerPlugin', ''))
            && preg_match('#^([\w/]+)\.(html|json|txt|raw)$#', $logger, $match)
        ) {
            $context->enabled = false;
            if (($data = $this->self->readData($match[1])) !== false) {
                $ext = $match[2];
                if ($ext === 'html') {
                    $this->response->setContent(strtr(LocalFS::fileGet($this->template), ['LOGGER_DATA' => $data]));
                } elseif ($ext === 'raw') {
                    $this->response->setContent($data)->setContentType('text/plain;charset=UTF-8');
                } elseif ($ext === 'txt') {
                    $txt = '';
                    foreach (json_parse($data) as $log) {
                        $txt .= strtr('[time][level][category][location] message', $log) . PHP_EOL;
                        $this->response->setContent($txt)->setContentType('text/plain;charset=UTF-8');
                    }
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
            $this->logger->info($this->request->get(), 'globals.request');
            $context->key = date('/ymd/His_') . Str::random(32);
        }

        if ($context->enabled) {
            $url = $this->router->createUrl("/?__loggerPlugin={$context->key}.html", true);
            $this->response->setHeader('X-Logger-Link', $url);
        }
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onLoggerLog(EventArgs $eventArgs)
    {
        $context = $this->context;

        /** @var \ManaPHP\Logging\Logger\Log $log */
        $log = $eventArgs->data['log'];

        if ($context->enabled) {
            $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
            $context->logs[] = [
                'time'     => date('H:i:s', $log->timestamp) . $ms,
                'category' => $log->category,
                'location' => "$log->file:$log->line",
                'level'    => $log->level,
                'message'  => $log->message,
            ];
        }
    }

    /**
     * @return void
     */
    public function onRequestEnd()
    {
        $context = $this->context;

        if ($context->enabled) {
            $this->self->writeData($context->key, $context->logs);
        }
    }

    /**
     * @return array
     */
    public function dump()
    {
        $data = parent::dump();

        $data['context']['logs'] = '***';

        return $data;
    }
}
