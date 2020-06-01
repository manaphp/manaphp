<?php

namespace ManaPHP\Curl;

use Countable;
use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

class Multi extends Component implements MultiInterface, Countable
{
    /**
     * @var string
     */
    protected $_proxy;

    /**
     * @var int
     */
    protected $_timeout = 10;

    /**
     * @var string
     */
    protected $_tmp_dir;

    /**
     * @var resource
     */
    protected $_template;

    /**
     * @var resource
     */
    protected $_mh;

    /**
     * @var \ManaPHP\Curl\Multi\Request[]
     */
    protected $_requests = [];

    /**
     * @var array
     */
    protected $_tmp_files = [];

    /**
     * CurlMulti constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['proxy'])) {
            $this->_proxy = $options['proxy'];
        }

        if (isset($options['timeout'])) {
            $this->_timeout = $options['timeout'];
        }

        if (isset($options['tmp_dir'])) {
            $this->_tmp_dir = $options['tmp_dir'];
        }

        $this->_template = $this->_createCurlTemplate();

        $this->_mh = curl_multi_init();

        $this->_tmp_dir = $this->alias->resolve('@data/CurlMulti');

        LocalFS::dirCreate($this->_tmp_dir);
    }

    /**
     * @return resource
     */
    protected function _createCurlTemplate()
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 8);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->_timeout);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko');
        curl_setopt($curl, CURLOPT_HEADER, 1);

        if ($this->_proxy) {
            $parts = parse_url($this->_proxy);
            $scheme = $parts['scheme'];
            if ($scheme === 'http') {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            } elseif ($scheme === 'sock4') {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
            } elseif ($scheme === 'sock5') {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }

            curl_setopt($curl, CURLOPT_PROXYPORT, $parts['port']);
            curl_setopt($curl, CURLOPT_PROXY, $parts['host']);
            if (isset($parts['user'], $parts['pass'])) {
                curl_setopt($curl, CURLOPT_PROXYUSERNAME, $parts['user']);
                curl_setopt($curl, CURLOPT_PROXYPASSWORD, $parts['pass']);
            }
        }

        /** @noinspection CurlSslServerSpoofingInspection */
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        /** @noinspection CurlSslServerSpoofingInspection */
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        return $curl;
    }

    /**
     * @param string|array|\ManaPHP\Curl\Multi\Request|\ManaPHP\Component $request
     * @param callable|array                                              $callbacks
     *
     * @return static
     */
    public function add($request, $callbacks = null)
    {
        if (is_string($request)) {
            $request = $this->_di->get('ManaPHP\Curl\Multi\Request', [$request, $callbacks]);
        } elseif (is_array($request)) {
            if (isset($request[1])) {
                foreach ($request as $r) {
                    $this->add($r, $callbacks);
                }
                return $this;
            } else {
                $request = $this->_di->get('ManaPHP\Curl\Multi\Request', [$request, $callbacks]);
            }
        }

        if (is_array($request->url)) {
            $queries = $request->url;
            unset($queries[0]);
            $url = $request->url[0];

            if ($queries) {
                $request->url = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($queries);
            } else {
                $request->url = $url;
            }
        }

        $headers = $request->headers;
        $options = $request->options;

        if (is_array($request->body)) {
            if (isset($headers['Content-Type']) && str_contains($headers['Content-Type'], '/json')) {
                $request->body = json_stringify($request->body);
            } else {
                $request->body = http_build_query($request->body);
            }
        }

        $curl = curl_copy_handle($this->_template);

        if (isset($headers['Cookie'])) {
            curl_setopt($curl, CURLOPT_COOKIE, $headers['Cookie']);
        }

        curl_setopt($curl, CURLOPT_URL, $request->url);
        switch ($request->method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $request->body);
                break;
        }

        curl_setopt($curl, CURLOPT_REFERER, $headers['Referer'] ?? $request->url);

        unset($headers['Referer'], $headers['User-Agent'], $headers['Cookie']);
        if ($headers) {
            $tr = [];
            foreach ($headers as $k => $v) {
                if (is_int($k)) {
                    $tr[] = $v;
                } else {
                    $tr[] = $k . ': ' . $v;
                }
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $tr);
        }

        if (isset($options['file'])) {
            if ($options['file'] === '') {
                $request->options['file'] = tempnam($this->_tmp_dir, 'curl_');
            }

            $file = fopen($request->options['file'], 'wb');
            fseek($file, 0);

            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_FILE, $file);
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
            $this->_tmp_files[(int)$curl] = $file;
        }

        if (isset($options['proxy'])) {
            $parts = parse_url($options['proxy']);
            $scheme = $parts['scheme'];
            if ($scheme === 'http') {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            } elseif ($scheme === 'sock4') {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
            } elseif ($scheme === 'sock5') {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }

            curl_setopt($curl, CURLOPT_PROXYPORT, $parts['port']);
            curl_setopt($curl, CURLOPT_PROXY, $parts['host']);
            if (isset($parts['user'], $parts['pass'])) {
                curl_setopt($curl, CURLOPT_PROXYUSERNAME, $parts['user']);
                curl_setopt($curl, CURLOPT_PROXYPASSWORD, $parts['pass']);
            }
        }

        foreach ($options as $k => $v) {
            if (is_int($v)) {
                curl_setopt($curl, $k, $v);
            }
        }

        curl_multi_add_handle($this->_mh, $curl);

        $request->start_time = microtime(true);

        $this->_requests[(int)$curl] = $request;

        return $this;
    }

    /**
     * @param string|array $url
     * @param string       $target
     * @param callable     $callback
     *
     * @return static
     */
    public function download($url, $target, $callback = null)
    {
        if (!LocalFS::fileExists($target)) {
            LocalFS::dirCreate(dir($target));

            $request = $this->_di->get('ManaPHP\Curl\Multi\Request', [$url, $callback]);

            $request->options['file'] = $target;

            $this->add($request);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function start()
    {
        while ($this->_requests) {
            $running = null;
            while (curl_multi_exec($this->_mh, $running) === CURLM_CALL_MULTI_PERFORM) {
                null;
            }
            curl_multi_select($this->_mh);

            while ($info = curl_multi_info_read($this->_mh)) {
                $curl = $info['handle'];
                $id = (int)$curl;

                $request = $this->_requests[$id];
                unset($this->_requests[$id]);

                if ($info['result'] === CURLE_OK) {
                    $response = $this->_di->get('ManaPHP\Curl\Multi\Response');

                    $response->request = $request;
                    $response->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    $response->stats = curl_getinfo($curl);

                    if (isset($this->_tmp_files[$id])) {
                        $response->body = $request->options['file'];
                        fclose($this->_tmp_files[$id]);
                        unset($this->_tmp_files[$id]);
                    } else {
                        $content = curl_multi_getcontent($curl);
                        $header_length = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
                        $response->body = substr($content, $header_length);
                        $response->headers = explode("\r\n", substr($content, 0, $header_length - 4));
                    }

                    $response->process_time = microtime(true) - $request->start_time;
                    $response->content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

                    $callbacks = $request->callbacks;
                    if ($callbacks === null) {
                        $this->onSuccess($response);
                    } elseif (is_callable($callbacks)) {
                        $callbacks($response);
                    } elseif (is_array($callbacks)) {
                        if (isset($callbacks['success'])) {
                            $callbacks['success']($response);
                        } else {
                            $this->onSuccess($response);
                        }
                    }
                } else {
                    $error = $this->_di->get('ManaPHP\Curl\Multi\Error');

                    $error->code = $info['result'];
                    $error->message = curl_error($curl);
                    $error->request = $request;

                    if (isset($this->_tmp_files[$id])) {
                        fclose($this->_tmp_files[$id]);
                        unlink($request->options['file']);
                        unset($this->_tmp_files[$id]);
                    }

                    $callbacks = $request->callbacks;

                    if (is_array($callbacks) && isset($callbacks['error'])) {
                        $callbacks['error']($error);
                    } else {
                        $this->onError($error);
                    }
                }

                curl_multi_remove_handle($this->_mh, $curl);
                curl_close($curl);

                unset($request, $response, $error, $callbacks, $info, $curl);
            }
        }

        return $this;
    }

    /**
     * @param \ManaPHP\Curl\Multi\Response $response
     *
     * @return false|null
     */
    public function onSuccess($response)
    {
        return null;
    }

    /**
     * @param \ManaPHP\Curl\Multi\Error $error
     *
     * @return false|null
     */
    public function onError($error)
    {
        $this->logger->error($error->message, 'curl_multi');

        return null;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->_requests);
    }
}