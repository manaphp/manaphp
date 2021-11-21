<?php

namespace ManaPHP\Http;

interface GlobalsInterface
{
    /**
     * @param array  $GET
     * @param array  $POST
     * @param array  $SERVER
     * @param string $RAW_BODY
     * @param array  $COOKIE
     * @param array  $FILES
     *
     * @return void
     */
    public function prepare($GET, $POST, $SERVER, $RAW_BODY = null, $COOKIE = [], $FILES = []);

    /**
     * @return \ManaPHP\Http\GlobalsContext
     */
    public function get();

    /**
     * @return array
     */
    public function getServer();

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setServer($name, $value);

    /**
     * @return array
     */
    public function getFiles();

    /**
     * @return array
     */
    public function getRequest();

    /**
     * @return string
     */
    public function getRawBody();

    /**
     * @return array
     */
    public function getCookie();

    /**
     * @param string $name
     *
     * @return static
     */
    public function unsetCookie($name);

    /**
     * @param string $name
     * @param string $value
     *
     * @return static
     */
    public function setCookie($name, $value);
}