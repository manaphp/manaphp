<?php
namespace ManaPHP\Net;

interface ConnectivityInterface
{
    /**
     * @param string $url
     * @param float  $time
     */
    public function test($url, $time = 0.1);

    /**
     * @param array $urls
     * @param float $time
     *
     * @return bool
     */
    public function wait($urls, $time = 0.1);
}