<?php
namespace ManaPHP {

    use ManaPHP\Debugger\Exception;
    use ManaPHP\Log\Logger;

    class Debugger extends Component implements DebuggerInterface
    {
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

        public function __construct($dependencyInjector = null)
        {
            $this->_dependencyInjector = $dependencyInjector;
            $self = $this;
            parent::peekEvents(function ($event, $source, $data) use ($self) {
                if ($event === 'logger:log') {
                    if (count($this->_log) <= $this->_log_max) {
                        $format = '[%time%][%level%] %message%';
                        $micro_date = explode(' ', microtime());
                        $replaces = [
                            '%time%' => date('H:i:s.', $micro_date[1]) . str_pad(ceil($micro_date[0] * 10000), '0', STR_PAD_LEFT),
                            '%level%' => $data['context']['level'],
                            '%message%' => $data['message']
                        ];
                        $self->_log[] = [
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
                            $this->_sql_prepared[$preparedSQL] += 1;
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
                    if ($this->_sql_count <= count($this->_sql_executed)) {
                        $this->_sql_executed[$this->_sql_count - 1]['time'] = round(microtime(true) - $this->_sql_beforeQueryTime, 3);
                        $this->_sql_executed[$this->_sql_count - 1]['row_count'] = $source->affectedRows();
                    }
                } elseif ($event === 'renderer:beforeRender') {
                    $this->_view[] = ['file' => $data['file'], 'vars' => $data['vars'], 'base_name' => basename(dirname($data['file'])) . '/' . basename($data['file'])];
                }
            });
        }

        public function dump($value, $name = null)
        {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            $this->_dump[] = ['name' => $name, 'value' => $value, 'file' => $backtrace[0]['file'], 'line' => $backtrace[0]['line']];
            return $this;
        }

        protected function _getBasic()
        {
            $loaded_extensions = get_loaded_extensions();
            sort($loaded_extensions, SORT_STRING | SORT_FLAG_CASE);
            return [
                'mvc' => $this->router->getModuleName() . '::' . $this->router->getControllerName() . '::' . $this->router->getActionName(),
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'request_uri' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
                'query_count' => $this->_sql_count,
                'execute_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4),
                'memory_usage' => (int)(memory_get_usage(true) / 1024) . 'k/' . (int)(memory_get_peak_usage(true) / 1024) . 'k',
                'system_time' => date('Y-m-d H:i:s'),
                'server_ip' => $_SERVER['SERVER_ADDR'],
                'client_ip' => $_SERVER['REMOTE_ADDR'],
                'operating_system' => php_uname(),
                'manaphp_version' => Version::get(),
                'php_version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'loaded_ini' => php_ini_loaded_file(),
                'loaded_extensions' => implode(', ', $loaded_extensions)
            ];
        }

        public function output($template = 'Default')
        {
            $data = [];
            $data['basic'] = $this->_getBasic();

            $data['dump'] = $this->_dump;
            $data['log'] = $this->_log;
            $data['log_levels'] = array_reverse($this->logger->getLevels());
            unset($data['log_levels']['ALL'], $data['log_levels']['OFF']);
            $data['log_level'] = Logger::LEVEL_DEBUG;
            $data['sql']['prepared'] = $this->_sql_prepared;
            $data['sql']['executed'] = $this->_sql_executed;
            $data['sql']['count'] = $this->_sql_count;
            /** @noinspection ImplicitMagicMethodCallInspection */
            $data['configure'] = $this->configure->__debugInfo();
            $data['view'] = $this->_view;
            if ($template === null) {
                return $data;
            }

            $template = str_replace('\\', '/', $template);

            if (strpos($template, '/') === false) {
                $template = __DIR__ . '/Debugger/Template/' . $template . '.php';
            }

            if (!is_file($template)) {
                throw new Exception('Template file is not existed: ' . $template);
            }

            ob_start();

            /** @noinspection PhpIncludeInspection */
            require $template;
            return ob_get_clean();
        }
    }
}