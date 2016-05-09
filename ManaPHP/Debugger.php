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

        protected $_exception;

        protected $_defaultTab = 'tab_basic';

        /**
         * @param bool $listenException
         *
         * @return static
         * @throws \ManaPHP\Di\Exception|\ManaPHP\Db\Exception|\ManaPHP\Exception
         */
        public function start($listenException = true)
        {
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

            if ($listenException) {
                set_exception_handler([$this, 'onUncaughtException']);
            }

            return $this;
        }

        protected function _getExceptionTraceArgs($args)
        {
            $data = [];

            foreach ($args as $arg) {
                if (is_scalar($arg)) {
                    return json_encode($arg);
                } else {
                    if (null) {
                        return 'null';
                    } else {
                        if (is_array($arg)) {
                            return 'arrays';
                        } else {
                            return '?';
                        }
                    }
                }
            }

            return $data;
        }

        /**
         * @param \Exception $exception
         *
         * @return bool
         */
        public function onUncaughtException(\Exception $exception)
        {
            for ($i = ob_get_level(); $i > 0; $i--) {
                ob_end_clean();
            }

            $callers = [];
            foreach ($exception->getTrace() as $trace) {
                $caller = [];

                if (isset($trace['class'])) {
                    $revoke = $trace['class'] . '->' . $trace['function'] . '(' . json_encode($this->_getExceptionTraceArgs($trace['args'])) . ')';
                } else {
                    $revoke = $trace['function'] . '(' . json_encode($this->_getExceptionTraceArgs($trace['args'])) . ')';
                }
                $caller['file'] = isset($trace['file']) ? $trace['file'] : $exception->getFile();
                $caller['line'] = isset($trace['line']) ? $trace['line'] : $exception->getLine();
                $caller['revoke'] = $revoke;
                $callers[] = $caller;
            }
            $this->_exception = [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'callers' => $callers
            ];

            $this->_defaultTab = 'tab_exception';

            echo $this->output();

            return true;
        }

        public function dump($value, $name = null)
        {
            $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
            $caller = isset($traces[1]) && $traces[1]['object'] instanceof $this ? $traces[1] : $traces[0];

            if ($name === null) {
                $lines = file($caller['file']);
                $str = $lines[$caller['line'] - 1];
                if (preg_match('#->dump\((.*)\)\s*;#', $str, $match) === 1) {
                    $name = $match[1];
                }
            }

            $this->_dump[] = [
                'name' => $name,
                'value' => $value,
                'file' => str_replace('\\', '/', $caller['file']),
                'line' => $caller['line'],
                'base_name' => basename($caller['file'])
            ];

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
            $data['configure'] = $this->_dependencyInjector->has('configure') ? $this->configure->__debugInfo() : [];
            $data['view'] = $this->_view;
            $data['exception'] = $this->_exception;
            $data['default_tab'] = $this->_defaultTab;
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