<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Http\Middleware;

/**
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class EtagMiddleware extends Middleware
{
    protected string $algo = 'md5';

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (isset($options['algo'])) {
            $this->algo = $options['algo'];
        }
    }

    public function onResponding(): void
    {
        if ($this->response->getStatusCode() !== 200 || !in_array($this->request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        if (($etag = $this->response->getHeader('ETag', '')) === '') {
            $etag = hash($this->algo, $this->response->getContent());
            $this->response->setETag($etag);
        }

        $if_none_match = $this->request->getIfNoneMatch();
        if ($if_none_match === $etag) {
            $this->response->removeHeader('ETag');
            $this->response->setNotModified();
        }
    }
}