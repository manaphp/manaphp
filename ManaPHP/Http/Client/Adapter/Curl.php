<?php
namespace ManaPHP\Http\Client\Adapter {

    use ManaPHP\Http\Client;

    class Curl extends Client
    {
        /**
         * @var array
         */
        protected $_curlResponseHeader = [];

        public function __construct($options = [], $headers = [])
        {
            parent::__construct($options, $headers);

            if (!function_exists('curl_init')) {
                throw new Exception('curl extension is not loaded: http://php.net/curl');
            }
        }

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

            switch ($type) {
                case 'GET':
                    break;
                case 'POST':
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                    break;
                case 'PATCH':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                    break;
                case 'PUT':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
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

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            if ($options['proxy']) {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($curl, CURLOPT_PROXY, $options['proxy']);
            }

            if ($options['file']) {
                if (!file_exists($options['file'])) {
                    throw new Exception('Uploaded file is not exists: ', $options['file']);
                }

                $stream = fopen($options['file'], 'wb');
                curl_setopt($curl, CURLOPT_FILE, $stream);
            }

            if ($options['ssl_certificates']) {
                curl_setopt($curl, CURLOPT_CAINFO, $options['ssl_certificates']);
            } else {
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            }

            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $options['verify_host'] ? 2 : 0);

            $this->_responseBody = curl_exec($curl);

            /** @noinspection NotOptimalIfConditionsInspection */
            if (curl_errno($curl) === 23 || curl_errno($curl) === 61) {
                curl_setopt($curl, CURLOPT_ENCODING, 'none');
                $this->_responseBody = curl_exec($curl);
            }

            if (isset($stream)) {
                fclose($stream);
            }

            if (curl_errno($curl)) {
                throw new Exception('cURL error ' . curl_errno($curl) . ':' . curl_error($curl));
            }

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            $this->_curlResponseHeader = curl_getinfo($curl);

            curl_close($curl);

            return $httpCode;
        }
    }
}