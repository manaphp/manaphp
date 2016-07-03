<?php
namespace ManaPHP\Http {

    use ManaPHP\Component;
    use ManaPHP\Http\Client\Exception;
    use ManaPHP\Utility\Text;

    /**
     * Class Client
     * @package ManaPHP\Http
     */
    abstract class Client extends Component implements ClientInterface, Client\AdapterInterface
    {
        /**
         * @var array
         */
        protected $_headers = [];

        /**
         * @var array
         */
        protected $_options = [];

        /**
         * @var string
         */
        protected $_responseBody = false;

        /**
         * Client constructor.
         *
         * @param array $options
         * - `timeout`: How long should we wait for a response?
         *    (integer, seconds, default: 10)
         * - `max_redirects`: How many times should we redirect 3xx before error?
         *    (integer, default: 10)
         * - `file`: File to stream the body to instead.
         *    (string, default: '')
         * - `proxy`: Proxy details to use for proxy by-passing and authentication
         *    (string, default: '')
         * - `ssl_certificates`: Should we verify SSL certificates? Allows passing in a custom
         *    certificate file as a string. (Using true uses the system-wide root
         *    certificate store instead, but this may have different behaviour
         *    across transports.)
         *    (string, default: 'xxx/ca.pem')
         * - `verify_host`: Should we verify the common name in the SSL certificate?
         *    (boolean: default, true)
         *
         * @param array $headers
         *
         * - `User-Agent`: User Agent to send to the server
         *   (string, default: php-requests/$version)
         */
        public function __construct($options = [], $headers = [])
        {
            parent::__construct();

            $this->_options = array_merge([
                'timeout' => 10,
                'max_redirects' => 10,
                'file' => '',
                'proxy' => '',
                'ssl_certificates' => __DIR__ . '\Client\Adapter\ca.pem',
                'verify_host' => true,
            ], $options);

            $this->_headers = array_merge(['User-Agent' => 'ManaPHP/httpClient'], $headers);
        }

        protected function request($type, $url, $data, $headers, $options)
        {
            $this->_responseBody = false;

            $url = $this->_buildUrl($url);
            if (preg_match('/^http(s)?:\/\//i', $url) !== 1) {
                throw new Exception('Only HTTP requests are handled: ' . $url);
            }

            $headers = array_merge($this->_headers, $headers);
            $options = array_merge($this->_options, $options);

            $this->fireEvent('httpClient:beforeRequest', ['type' => $type, 'url' => &$url, 'headers' => &$headers, 'data' => &$data, 'options' => &$options]);
            $httpCode = $this->_request($type, $url, $data, $headers, $options);
            $this->fireEvent('httpClient:afterRequest', ['httpCode' => &$httpCode, 'responseBody' => &$this->_responseBody]);

            return $httpCode;
        }

        protected function _buildUrl($url)
        {
            if (is_string($url)) {
                return $url;
            }

            list($url, $data) = $url;
            return $url . (Text::contains($url, '?') ? '&' : '?') . http_build_query($data);
        }

        public function get($url, $headers = [], $options = [])
        {
            return $this->request('GET', $url, null, $headers, $options);
        }

        public function post($url, $data = [], $headers = [], $options = [])
        {
            return $this->request('POST', $url, $data, $headers, $options);
        }

        public function delete($url, $headers = [], $options = [])
        {
            return $this->request('DELETE', $url, null, $headers, $options);
        }

        public function put($url, $data = [], $headers = [], $options = [])
        {
            return $this->request('PUT', $url, $data, $headers, $options);
        }

        public function patch($url, $data = [], $headers = [], $options = [])
        {
            return $this->request('PATCH', $url, $data, $headers, $options);
        }

        public function getResponseBody()
        {
            return $this->_responseBody;
        }
    }
}