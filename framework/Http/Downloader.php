<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;

class Downloader implements DownloaderInterface
{
    #[Autowired] protected AliasInterface $alias;

    public const USER_AGENT_IE = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko';

    public function download(array $files, mixed $options = []): array
    {
        if (\is_int($options)) {
            $options = ['concurrent' => $options];
        } elseif (\is_float($options)) {
            $options = ['timeout' => $options];
        } elseif (\is_string($options)) {
            $options = [preg_match('#^https?://#', $options) ? CURLOPT_REFERER : CURLOPT_USERAGENT => $options];
        }

        $mh = curl_multi_init();

        $template = curl_init();

        if (isset($options['timeout'])) {
            $timeout = $options['timeout'];
            unset($options['timeout']);
        } else {
            $timeout = 10;
        }

        if (isset($options['concurrent'])) {
            $concurrent = $options['concurrent'];
            unset($options['concurrent']);
        } else {
            $concurrent = 10;
        }

        curl_setopt($template, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($template, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($template, CURLOPT_USERAGENT, self::USER_AGENT_IE);
        curl_setopt($template, CURLOPT_HEADER, 0);
        /** @noinspection CurlSslServerSpoofingInspection */
        curl_setopt($template, CURLOPT_SSL_VERIFYHOST, false);
        /** @noinspection CurlSslServerSpoofingInspection */
        curl_setopt($template, CURLOPT_SSL_VERIFYPEER, false);

        foreach ($options as $k => $v) {
            if (\is_int($k)) {
                curl_setopt($template, $k, $v);
            }
        }

        foreach ($files as $url => $file) {
            $file = $this->alias->resolve($file);
            if (is_file($file)) {
                unset($files[$url]);
            } else {
                LocalFS::dirCreate(\dirname($file));
                $files[$url] = $file;
            }
        }

        $handles = [];
        $failed = [];
        do {
            foreach ($files as $url => $file) {
                if (\count($handles) === $concurrent) {
                    break;
                }
                $curl = curl_copy_handle($template);
                $id = (int)$curl;

                curl_setopt($curl, CURLOPT_URL, $url);
                $fp = fopen($file . '.tmp', 'wb');
                curl_setopt($curl, CURLOPT_FILE, $fp);

                curl_multi_add_handle($mh, $curl);
                $handles[$id] = ['url' => $url, 'file' => $file, 'fp' => $fp];

                unset($files[$url]);
            }

            $running = 0;
            while (curl_multi_exec($mh, $running) === CURLM_CALL_MULTI_PERFORM) {
                null;
            }

            usleep(100);

            while ($info = curl_multi_info_read($mh)) {
                $curl = $info['handle'];
                $id = (int)$curl;

                $url = $handles[$id]['url'];
                $file = $handles[$id]['file'];

                fclose($handles[$id]['fp']);

                if ($info['result'] === CURLE_OK) {
                    rename($file . '.tmp', $file);
                } else {
                    $failed[$url] = curl_strerror($curl);
                    unlink($file . '.tmp');
                }

                curl_multi_remove_handle($mh, $curl);
                curl_close($curl);

                unset($handles[$id]);
            }
        } while ($handles);

        curl_multi_close($mh);
        curl_close($template);

        return $failed;
    }
}