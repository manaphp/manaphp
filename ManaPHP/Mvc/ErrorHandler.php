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
     */
    public function handle($exception)
    {
        if ($exception instanceof \ManaPHP\Exception) {
            if ($exception->getStatusCode() < 400) {
                return;
            }
            $this->response->setStatus($exception->getStatusCode(), $exception->getStatusText());
            if ($exception->getStatusCode() === 500) {
                $this->logException($exception);
            }
        } else {
            $this->response->setStatus(500, 'Internal Server Error');
            $this->logException($exception);
        }

        $this->response->setContent($this->render($exception));
    }

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

    /**
     * @param \Exception|\ManaPHP\Exception $exception
     *
     * @return string
     */
    public function render($exception)
    {
        for ($level = ob_get_level(); $level >=0; $level--) {
            ob_end_clean();
        }

        if ($this->configure->debug) {
            if ($this->renderer->exists('@app/Views/Errors/debug')) {
                return $this->renderer->render('@app/Views/Errors/debug', ['exception' => $exception]);
            } else {
                return $this->renderer->render('@manaphp/Mvc/ErrorHandler/Views/debug', ['exception' => $exception]);
            }
        }

        $statusCode = $exception instanceof \ManaPHP\Exception ? $exception->getStatusCode() : 500;

        foreach (["@app/Views/Errors/$statusCode",
                     '@app/Views/Errors/error'] as $template) {
            if ($this->renderer->exists($template)) {
                return $this->renderer->render($template, ['statusCode' => $statusCode, 'exception' => $exception]);
            }
        }
        $statusText = $exception instanceof \ManaPHP\Exception ? $exception->getStatusText() : 'App Error';
        return "<html><title>$statusCode: $statusText</title><body>$statusCode: $statusText</body></html>";
    }
}