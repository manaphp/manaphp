<?php

namespace ManaPHP\Bos;

use ManaPHP\Helper\Arr;
use ManaPHP\Helper\LocalFS;

class Command extends \ManaPHP\Cli\Command
{
    /**
     * list buckets
     *
     * @return void
     */
    public function listBucketsAction()
    {
        foreach ($this->bosClient->listBuckets() as $bucket) {
            $this->console->writeLn($bucket);
        }
    }

    /**
     * create new bucket
     *
     * @param string $bucket
     * @param string $base_url
     *
     * @return void
     */
    public function createBucketAction($bucket, $base_url = '')
    {
        $this->console->writeLn($this->bosClient->createBucket($bucket, $base_url));
    }

    /**
     * list all objects of one bucket
     *
     * @param string $bucket    the bucket name of objects
     * @param string $key       the key of object
     * @param string $prefix    the prefix of keys
     * @param string $mime_type the mime-type of object
     * @param string $extension the extension of object
     *
     * @return void
     */
    public function listAction($bucket, $key = '', $prefix = '', $mime_type = '', $extension = '')
    {
        $filters = [];

        $filters['key'] = $key;
        $filters['prefix'] = $prefix;
        $filters['mime_type'] = $mime_type;
        $filters['extension'] = $extension;

        $filters = Arr::trim($filters);

        $response = $this->bosClient->listObjects($bucket, $filters);
        $this->console->write($response);
    }

    /**
     * import local directory to bucket
     *
     * @param string $bucket
     * @param string $dir
     * @param string $prefix
     *
     * @return int
     */
    public function importAction($bucket, $dir, $prefix)
    {
        if (!LocalFS::dirExists($dir)) {
            return $this->console->error(['`:dir` directory is not exists', 'dir' => $dir]);
        }

        $this->recursiveImport($dir, $bucket, $prefix);

        return 0;
    }

    /**
     * @param string $dir
     * @param string $bucket
     * @param string $prefix
     *
     * @return void
     */
    protected function recursiveImport($dir, $bucket, $prefix)
    {
        $dir = rtrim($dir, '\\/');
        $prefix = trim($prefix, '/');

        foreach (LocalFS::scandir($dir) as $item) {
            $file = "$dir/$item";
            if (LocalFS::fileExists($file)) {
                $response = $this->bosClient->putObject($file, $bucket, "$prefix/$item");
                $this->console->writeLn($response);
            } else {
                $this->recursiveImport($file, $bucket, "$prefix/$item");
            }
        }
    }

    /**
     * export object to local directory
     *
     * @param string $bucket
     * @param string $dir
     * @param string $prefix
     * @param string $key
     *
     * @return void
     */
    public function exportAction($bucket, $dir = '', $prefix = '', $key = '')
    {
        $filters = [];
        $filters['prefix'] = $prefix;
        $filters['key'] = $key;

        if (!$dir) {
            $dir = "@tmp/bos_export/$bucket";
        }

        $dir = rtrim($dir, '/');

        $files = [];
        foreach ($this->bosClient->listObjects($bucket, $filters) as $object) {
            $files[$object['url']] = $dir . '/' . $object['key'];
        }

        $this->httpClient->download($files);

        $this->console->writeLn(['download files to `:dir` directory', 'dir' => $dir]);
    }
}