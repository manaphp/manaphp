<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\Filter;
use ManaPHP\Http\Filter\RespondingFilterInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;

class EtagFilter extends Filter implements RespondingFilterInterface
{
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;

    protected string $algo;

    public function __construct(string $algo = 'md5')
    {
        $this->algo = $algo;
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