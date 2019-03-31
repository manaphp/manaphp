<?php
namespace ManaPHP\Http;

class Application extends \ManaPHP\Application
{
    public function __construct($loader = null)
    {
        parent::__construct($loader);

        $this->eventsManager->attachEvent('request:begin', [$this, 'generateRequestId']);
        $this->eventsManager->attachEvent('request:authenticate', [$this, 'authenticate']);
        $this->eventsManager->attachEvent('request:authorize', [$this, 'authorize']);
    }

    public function generateRequestId()
    {
        if (!$this->request->hasServer('HTTP_X_REQUEST_ID')) {
            if (function_exists('random_bytes')) {
                $request_id = random_bytes(15);
            } else {
                $request_id = substr(md5(microtime() . mt_rand(), true), 0, 15);
            }

            $globals = $this->request->getGlobals();

            $globals->_SERVER['HTTP_X_REQUEST_ID'] = 'aa' . bin2hex($request_id);
        }
    }

    protected function _prepareGlobals()
    {
        $globals = $this->request->getGlobals();

        $globals->_GET = $_GET;
        $globals->_POST = $_POST;
        $globals->_REQUEST = $_REQUEST;
        $globals->_FILES = $_FILES;
        $globals->_COOKIE = $_COOKIE;
        $globals->_SERVER = $_SERVER;

        if (!$this->configure->compatible_globals) {
            unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
            foreach ($_SERVER as $k => $v) {
                if (strpos('DOCUMENT_ROOT,SERVER_SOFTWARE,SCRIPT_NAME,SCRIPT_FILENAME', $k) === false) {
                    unset($_SERVER[$k]);
                }
            }
        }
    }

    public function authenticate()
    {
        $this->identity->authenticate();
    }

    public function authorize()
    {
        $this->authorization->authorize();
    }
}