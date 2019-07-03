<?php
namespace ManaPHP\Http;

use ManaPHP\Component;

/**
 * Class Server
 * @package ManaPHP\Http
 *
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Http\Response         $response
 */
abstract class Server extends Component implements ServerInterface
{
    /**
     * @var bool
     */
    protected $_compatible_globals = false;

    /**
     * @var string
     */
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = '1983';

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
        if (isset($options['compatible_globals'])) {
            $this->_compatible_globals = (bool)$options['compatible_globals'];
            unset($options['compatible_globals']);
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
            foreach (glob($this->_doc_root . '/*', GLOB_ONLYDIR) as $dir) {
                $this->_root_files[] = basename($dir);
            }

            $this->_mime_types = $this->getMimeTypes();
        }
    }

    public function log($level, $message)
    {
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }

    public function getMimeTypes()
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
}