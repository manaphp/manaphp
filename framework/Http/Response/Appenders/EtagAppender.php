<?php
declare(strict_types=1);

namespace ManaPHP\Http\Response\Appenders;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response\AppenderInterface;
use ManaPHP\Http\ResponseInterface;
use function in_array;

class EtagAppender implements AppenderInterface
{
    #[Autowired] protected string $algo = 'md5';

    public function append(RequestInterface $request, ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200 || !in_array($request->method(), ['GET', 'HEAD'], true)) {
            return;
        }

        if (($etag = $response->getHeader('ETag', '')) === '') {
            $etag = hash($this->algo, $response->getContent());
            $response->setETag($etag);
        }

        $if_none_match = $request->header('if-none-match');
        if ($if_none_match === $etag) {
            $response->removeHeader('ETag');
            $response->setNotModified();
        }
    }
}