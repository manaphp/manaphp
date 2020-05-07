<?php

namespace ManaPHP\Http;

use ManaPHP\Aop\Unaspectable;
use ManaPHP\Component;

/**
 * Class Server
 *
 * @package ManaPHP\Http
 *
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Http\Globals\ManagerInterface $globalsManager
 */
abstract class Server extends Component implements ServerInterface, Unaspectable
{
    /**
     * @var bool
     */
    protected $_use_globals = false;

    /**
     * @var string
     */
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = '9501';

    /**
     * @var array
     */
    protected $_mime_types;

    /**
     * @var array
     */
    protected $_root_files;

    /**
     * @var string
     */
    protected $_doc_root;

    /**
     * Server constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['use_globals'])) {
            $this->_use_globals = (bool)$options['use_globals'];
            unset($options['use_globals']);
        }

        if (isset($options['max_request']) && $options['max_request'] < 1) {
            $options['max_request'] = 1;
        }

        if (isset($options['host'])) {
            $this->_host = $options['host'];
            unset($options['host']);
        }

        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
            unset($options['port']);
        }

        $this->_doc_root = $this->alias->resolve('@public');

        if (isset($options['enable_static_handler'])) {
            $this->_root_files = $this->_getRootFiles();
            $this->_mime_types = $this->_getMimeTypes();
        }

        if (ob_get_level() === 0) {
            ob_start();
        }
    }

    public function log($level, $message)
    {
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }

    /**
     * @return array
     */
    protected function _getRootFiles()
    {
        $files = [];
        foreach (glob($this->_doc_root . '/*') as $file) {
            $file = basename($file);
            if ($file[0] === '.' || pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                continue;
            }

            $files[] = basename($file);
        }

        return $files;
    }

    /**
     * @return array
     */
    protected function _getMimeTypes()
    {
        $mime_types = [];
        foreach (file(__DIR__ . '/Server/mime.types', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos($line, ';') === false) {
                continue;
            }

            $line = trim($line);
            $line = trim($line, ';');

            $parts = preg_split('#\s+#', $line, -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) < 2) {
                continue;
            }

            foreach ($parts as $k => $part) {
                if ($k !== 0) {
                    $mime_types[$part] = $parts[0];
                }
            }
        }

        return $mime_types;
    }

    /**
     * @return bool|string
     */
    protected function _isStaticFile()
    {
        $uri = $this->request->getServer('REQUEST_URI');
        $file = ($pos = strpos($uri, '?')) === false ? substr($uri, 1) : substr($uri, 1, $pos - 1);

        if ($file === 'favicon.ico') {
            return '/favicon.ico';
        } elseif (in_array($file, $this->_root_files, true)) {
            return $file;
        } elseif (($pos = strpos($file, '/')) === false) {
            return false;
        } else {
            $level1 = substr($file, 0, $pos);
            return in_array($level1, $this->_root_files, true) ? $file : false;
        }
    }

    public function dump()
    {
        $data = parent::dump();

        unset($data['_mime_types'], $data['_context'], $data['_server']);

        return $data;
    }
}