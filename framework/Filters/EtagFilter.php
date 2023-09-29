<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestResponsing;

class EtagFilter
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;

    #[Autowired] protected string $algo = 'md5';

    public function onResponding(#[Event] RequestResponsing $event): void
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