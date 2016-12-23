<?php
namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Client\Exception as ClientException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Http\Client
 *
 * @package httpClient
 */
class Client extends Component implements ClientInterface
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
     * @var array
     */
    protected $_curlResponseHeader = [];

    /**
     * @var bool
     */
    protected $_peek = false;

    /**
     * Client constructor.
     *
     * @param array $options
     *    - `timeout`: How long should we wait for a response?
     *    (integer, seconds, default: 10)
     *    - `max_redirects`: How many times should we redirect 3xx before error?
     *    (integer, default: 10)
     *    (string, default: '')
     *    - `proxy`: Proxy details to use for proxy by-passing and authentication
     *    (string, default: '')
     *    - `ssl_certificates`: Should we verify SSL certificates? Allows passing in a custom
     *    certificate file as a string. (Using true uses the system-wide root
     *    certificate store instead, but this may have different behaviour
     *    across transports.)
     *    (string, default: 'xxx/ca.pem')
     *    - `verify_host`: Should we verify the common name in the SSL certificate?
     *    (bool: default, true)
     *
     * @param array $headers
     *
     * - `User-Agent`: User Agent to send to the server
     *   (string, default: php-requests/$version)
     *
     * @throws \ManaPHP\Http\Client\Exception
     */
    public function __construct($options = [], $headers = [])
    {
        if (!function_exists('curl_init')) {
            throw new ClientException('curl extension is not loaded: http://php.net/curl'/**m01df15300bf1482df*/);
        }

        $defaultOptions = [
            'timeout' => 10,
            'max_redirects' => 10,
            'proxy' => '',
            'ssl_certificates' => '@manaphp/Http/Client/ca.pem',
            'verify_host' => true,
        ];
        $this->_options = array_merge($defaultOptions, $options);

        $defaultHeaders = ['User-Agent' => 'ManaPHP/httpClient'];
        $this->_headers = array_merge($defaultHeaders, $headers);
    }

    /**
     * @param string $proxy
     * @param bool   $peek
     *
     * @return static
     */
    public function setProxy($proxy = '127.0.0.1:8888', $peek = true)
    {
        $this->_options['proxy'] = $proxy;

        $this->_peek = $peek;

        return $this;
    }

    /**
     * @param string       $type
     * @param string|array $url
     * @param string|array $data
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     * @throws \ManaPHP\Http\Client\Exception
     */
    protected function request($type, $url, $data, $headers, $options)
    {
        $this->_responseBody = false;

        $url = $this->_buildUrl($url);
        if (preg_match('/^http(s)?:\/\//i', $url) !== 1) {
            throw new ClientException('only HTTP requests can be handled: `:url`'/**m06c8af26e23f01884*/, ['url' => $url]);
        }

        $headers = array_merge($this->_headers, $headers);
        $options = array_merge($this->_options, $options);

        $eventData = ['type' => $type, 'url' => &$url, 'headers' => &$headers, 'data' => &$data, 'options' => &$options];
        $this->fireEvent('httpClient:beforeRequest', $eventData);
        $httpCode = $this->_request($type, $url, $data, $headers, $options);
        $eventData = [
            'type' => $type,
            'url' => $url,
            'headers' => $headers,
            'data' => $data,
            'options' => $options,
            'httpCode' => &$httpCode,
            'responseBody' => &$this->_responseBody
        ];
        $this->fireEvent('httpClient:afterResponse', $eventData);
        return $httpCode;
    }

    /**
     * @param string       $type
     * @param string       $url
     * @param string|array $data
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     * @throws \ManaPHP\Http\Client\Exception
     */
    public function _request($type, $url, $data, $headers, $options)
    {
        $this->_curlResponseHeader = [];

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);

        if ($options['max_redirects'] > 0) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS, $options['max_redirects']);
        } else {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        }

        if (isset($headers['Cookie'])) {
            curl_setopt($curl, CURLOPT_COOKIE, $headers['Cookie']);
        }

        if (is_array($data)) {
            $hasFiles = false;
            /** @noinspection ForeachSourceInspection */
            foreach ($data as $k => $v) {
                if (is_string($v) && $v[0] === '@') {
                    $hasFiles = true;
                    if (class_exists('CURLFile')) {
                        $file = substr($v, 1);

                        $parts = explode(';', $file);

                        if (count($parts) === 1) {
                            $data[$k] = new \CURLFile($file);
                        } else {
                            $file = $parts[0];
                            $types = explode('=', $parts[1]);
                            if ($types[0] !== 'type' || count($types) !== 2) {
                                throw new ClientException('`:file` file name format is invalid'/**m05efb8755481bd2eb*/, ['file' => $v]);
                            } else {
                                $data[$k] = new \CURLFile($file, $types[1]);
                            }
                        }
                    }
                } elseif (is_object($v)) {
                    $hasFiles = true;
                }
            }

            if (!$hasFiles) {
                $data = http_build_query($data);
            }
        }

        switch ($type) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PATCH':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $options['timeout']);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $options['timeout']);
        curl_setopt($curl, CURLOPT_REFERER, isset($headers['Referer']) ? $headers['Referer'] : $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $headers['User-Agent']);

        unset($headers['Referer'], $headers['User-Agent'], $headers['Cookie']);

        $formatted_headers = [];
        foreach ($headers as $k => $v) {
            if (is_int($k)) {
                $formatted_headers[] = $v;
            } else {
                $formatted_headers[] = $k . ': ' . $v;
            }
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $formatted_headers);

        if ($options['proxy']) {
            curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($curl, CURLOPT_PROXY, $options['proxy']);
        }

        if ($options['ssl_certificates']) {
            if ($this->_peek && $this->_options['proxy'] !== '') {
                curl_setopt($curl, CURLOPT_CAINFO, $this->alias->resolve('@manaphp/Http/Client/fiddler.cer'));
            } else {
                curl_setopt($curl, CURLOPT_CAINFO, $this->alias->resolve($options['ssl_certificates']));
            }
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $options['verify_host'] ? 2 : 0);

        $this->_responseBody = curl_exec($curl);

        $err = curl_errno($curl);
        if ($err === 23 || $err === 61) {
            curl_setopt($curl, CURLOPT_ENCODING, 'none');
            $this->_responseBody = curl_exec($curl);
        }

        if (curl_errno($curl)) {
            throw new ClientException('cURL error: :code::message'/**m0d2c9a60b72a0362f*/, ['code' => curl_errno($curl), 'message' => curl_error($curl)]);
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $this->_curlResponseHeader = curl_getinfo($curl);

        curl_close($curl);

        return $httpCode;
    }

    /**
     * @param string|array $url
     *
     * @return string
     */
    protected function _buildUrl($url)
    {
        if (is_string($url)) {
            return $url;
        }

        return $url[0] . (Text::contains($url[0], '?') ? '&' : '?') . http_build_query($url[1]);
    }

    /**
     * @param array|string $url
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     * @throws \ManaPHP\Http\Client\Exception
     */
    public function get($url, $headers = [], $options = [])
    {
        return $this->request('GET', $url, null, $headers, $options);
    }

    /**
     * @param array|string $url
     * @param string|array $data
     * @param array        $headers
     * @param array        $options
     *
     * @return mixed
     * @throws \ManaPHP\Http\Client\Exception
     */
    public function post($url, $data = [], $headers = [], $options = [])
    {
        return $this->request('POST', $url, $data, $headers, $options);
    }

    /**
     * @param array|string $url
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     * @throws \ManaPHP\Http\Client\Exception
     */
    public function delete($url, $headers = [], $options = [])
    {
        return $this->request('DELETE', $url, null, $headers, $options);
    }

    /**
     * @param array|string $url
     * @param string|array $data
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     * @throws \ManaPHP\Http\Client\Exception
     */
    public function put($url, $data = [], $headers = [], $options = [])
    {
        return $this->request('PUT', $url, $data, $headers, $options);
    }

    /**
     * @param array|string $url
     * @param string|array $data
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     * @throws \ManaPHP\Http\Client\Exception
     */
    public function patch($url, $data = [], $headers = [], $options = [])
    {
        return $this->request('PATCH', $url, $data, $headers, $options);
    }

    /**
     * @return string
     */
    public function getResponseBody()
    {
        return $this->_responseBody;
    }
}