<?php

namespace ManaPHP\Http\Globals;

use ManaPHP\Component;
use ManaPHP\Http\Globals\Proxies\CookieProxy;
use ManaPHP\Http\Globals\Proxies\FilesProxy;
use ManaPHP\Http\Globals\Proxies\GetProxy;
use ManaPHP\Http\Globals\Proxies\PostProxy;
use ManaPHP\Http\Globals\Proxies\RequestProxy;
use ManaPHP\Http\Globals\Proxies\ServerProxy;
use ManaPHP\Http\Globals\Proxies\SessionProxy;

/**
 * Class Manager
 *
 * @package ManaPHP\Http
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Manager extends Component implements ManagerInterface
{
    /**
     * @return void
     */
    public function proxy()
    {
        /** @var \ManaPHP\Http\Request $request */
        $request = $this->request;
        $request->getContext()->_SERVER = $_SERVER;

        $_GET = new GetProxy($request);
        $_POST = new PostProxy($request);
        $_REQUEST = new RequestProxy($request);
        $_FILES = new FilesProxy($request);
        $_COOKIE = new CookieProxy($request);
        $_SESSION = new SessionProxy($request);
        $_SERVER = new ServerProxy($request);
    }
}