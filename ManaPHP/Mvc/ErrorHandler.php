<?php
namespace ManaPHP\Mvc;

/**
 * Class ManaPHP\Mvc\ErrorHandler
 *
 * @package ManaPHP\Mvc
 * @property \ManaPHP\Http\RequestInterface $request
 */
class ErrorHandler extends \ManaPHP\ErrorHandler
{
    /**
     * @param \Exception $exception
     *
     * @return array
     */
    public function getLogData($exception)
    {
        $data = [];
        $data['method'] = $_SERVER['REQUEST_METHOD'];
        $data['url'] = $this->request->getUrl();
        $data['GET'] = $_GET;
        $data['POST'] = $_POST;

        $data['client_ip'] = $this->request->getClientAddress();

        return array_merge($data, parent::getLogData($exception));
    }
}