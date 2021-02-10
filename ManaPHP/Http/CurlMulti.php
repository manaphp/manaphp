<?php

namespace ManaPHP\Http;

use Countable;
use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\AliasInterface          $alias
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class CurlMulti extends Component implements CurlMultiInterface, Countable
{
    /**
     * @var string
     */
    protected $proxy;

    /**
     * @var int
     */
    protected $timeout = 10;

    /**
     * @var resource
     */
    protected $template;

    /**
     * @var resource
     */
    protected $mh;

    /**
     * @var \ManaPHP\Http\CurlMulti\Request[]
     */
    protected $requests = [];

    /**
     * @var array
     */
    protected $files = [];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['proxy'])) {
            $this->proxy = $options['proxy'];
        }

        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }

        $this->template = $this->createCurlTemplate();

        $this->mh = curl_multi_init();

        LocalFS::dirCreate('@data/curlMulti');
    }

    /**
     * @return resource
     */
    protected function createCurlTemplate()
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 8);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko');
        curl_setopt($curl, CURLOPT_HEADER, 1);

        if ($this->proxy) {
            $parts = parse_url($this->proxy);
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
     * @param string|array|\ManaPHP\Http\CurlMulti\Request|\ManaPHP\Component $request
     * @param callable|array                                                  $callbacks
     *
     * @return static
     */
    public function add($request, $callbacks = null)
    {
        if (is_string($request)) {
            $request = $this->getNew('ManaPHP\Http\CurlMulti\Request', [$request, $callbacks]);
        } elseif (is_array($request)) {
            if (isset($request[1])) {
                foreach ($request as $r) {
                    $this->add($r, $callbacks);
                }
                return $this;
            } else {
                $request = $this->getNew('ManaPHP\Http\CurlMulti\Request', [$request, $callbacks]);
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

        $curl = curl_copy_handle($this->template);

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
                $request->options['file'] = tempnam($this->alias->resolve('@data/curlMulti'), 'curl_');
            }

            $file = fopen($request->options['file'], 'wb');
            fseek($file, 0);

            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_FILE, $file);
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
            $this->files[(int)$curl] = $file;
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

        curl_multi_add_handle($this->mh, $curl);

        $request->start_time = microtime(true);

        $this->requests[(int)$curl] = $request;

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

            $request = $this->getNew('ManaPHP\Http\CurlMulti\Request', [$url, $callback]);

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
        while ($this->requests) {
            $running = null;
            while (curl_multi_exec($this->mh, $running) === CURLM_CALL_MULTI_PERFORM) {
                null;
            }
            curl_multi_select($this->mh);

            while ($info = curl_multi_info_read($this->mh)) {
                $curl = $info['handle'];
                $id = (int)$curl;

                $request = $this->requests[$id];
                unset($this->requests[$id]);

                if ($info['result'] === CURLE_OK) {
                    $response = $this->getNew('ManaPHP\Http\CurlMulti\Response');

                    $response->request = $request;
                    $response->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    $response->stats = curl_getinfo($curl);

                    if (isset($this->files[$id])) {
                        $response->body = $request->options['file'];
                        fclose($this->files[$id]);
                        unset($this->files[$id]);
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
                    $error = $this->getNew('ManaPHP\Http\CurlMulti\Error');

                    $error->code = $info['result'];
                    $error->message = curl_error($curl);
                    $error->request = $request;

                    if (isset($this->files[$id])) {
                        fclose($this->files[$id]);
                        unlink($request->options['file']);
                        unset($this->files[$id]);
                    }

                    $callbacks = $request->callbacks;

                    if (is_array($callbacks) && isset($callbacks['error'])) {
                        $callbacks['error']($error);
                    } else {
                        $this->onError($error);
                    }
                }

                curl_multi_remove_handle($this->mh, $curl);
                curl_close($curl);

                unset($request, $response, $error, $callbacks, $info, $curl);
            }
        }

        return $this;
    }

    /**
     * @param \ManaPHP\Http\CurlMulti\Response $response
     *
     * @return false|null
     */
    public function onSuccess($response)
    {
        return null;
    }

    /**
     * @param \ManaPHP\Http\CurlMulti\Error $error
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
        return count($this->requests);
    }
}