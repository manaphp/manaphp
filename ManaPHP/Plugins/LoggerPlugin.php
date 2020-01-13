<?php
namespace ManaPHP\Plugins;

use ManaPHP\Event\EventArgs;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Plugin;

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

    public $logs = [];
}

/**
 * Class LoggerPlugin
 * @package ManaPHP\Plugins
 * @property-read \ManaPHP\Plugins\LoggerPluginContext $_context
 */
class LoggerPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $_enabled;

    /**
     * @var int
     */
    protected $_ttl = 300;

    /**
     * @var string
     */
    protected $_prefix;

    /**
     * @var string
     */
    protected $_template = '@manaphp/Plugins/LoggerPlugin/Template.html';

    /**
     * LoggerPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (MANAPHP_CLI) {
            $this->_enabled = false;
        } elseif (isset($options['enabled'])) {
            $this->_enabled = (bool)$options['enabled'];
        } elseif (!in_array($this->configure->env, ['dev', 'test'], true)) {
            $this->_enabled = false;
        }

        if (isset($options['ttl'])) {
            $this->_ttl = (int)$options['ttl'];
        }

        $this->_prefix = $options['prefix'] ?? "cache:{$this->configure->id}:loggerPlugin:";

        if (isset($options['template'])) {
            $this->_template = $options['template'];
        }

        if ($this->_enabled !== false) {
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
    protected function _readData($key)
    {
        if ($this->_ttl) {
            $data = $this->redis->get($this->_prefix . $key);
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
    protected function _writeData($key, $data)
    {
        $content = gzencode(json_stringify($data, JSON_PARTIAL_OUTPUT_ON_ERROR));
        if ($this->_ttl) {
            $this->redis->set($this->_prefix . $key, $content, $this->_ttl);
        } else {
            LocalFS::filePut("@data/loggerPlugin/{$key}.zip", $content);
        }
    }

    public function onRequestBegin()
    {
        $context = $this->_context;

        if (($logger = $this->request->get('__loggerPlugin', '')) && preg_match('#^([\w/]+)\.(html|json|txt|raw)$#', $logger, $match)) {
            $context->enabled = false;
            if (($data = $this->_readData($match[1])) !== false) {
                $ext = $match[2];
                if ($ext === 'html') {
                    $this->response->setContent(strtr(LocalFS::fileGet($this->_template), ['LOGGER_DATA' => $data]));
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
        } elseif (strpos($this->request->getServer('HTTP_USER_AGENT'), 'ApacheBench') !== false) {
            $context->enabled = false;
        } else {
            $context->enabled = true;
            $this->logger->info($this->request->getGlobals()->_REQUEST, 'globals.request');
            $context->key = date('/ymd/His_') . $this->random->getBase(32);
        }

        if ($context->enabled) {
            $url = $this->router->createUrl("/?__loggerPlugin={$context->key}.html", true);
            $this->response->setHeader('X-Logger-Link', $url);
        }
    }

    public function onLoggerLog(EventArgs $eventArgs)
    {
        $context = $this->_context;

        /** @var \ManaPHP\Logger\Log $log */
        $log = $eventArgs->data;

        if ($context->enabled) {
            $context->logs[] = [
                'time' => date('H:i:s.', $log->timestamp) . sprintf('%.03d', ($log->timestamp - (int)$log->timestamp) * 1000),
                'category' => $log->category,
                'location' => "$log->file:$log->line",
                'level' => $log->level,
                'message' => $log->message,
            ];
        }
    }

    public function onRequestEnd()
    {
        $context = $this->_context;

        if ($context->enabled) {
            $this->_writeData($context->key, $context->logs);
        }
    }

    public function dump()
    {
        $data = parent::dump();

        $data['_context']['logs'] = '***';

        return $data;
    }
}
