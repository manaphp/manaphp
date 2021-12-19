<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Http\ResponseInterface        $response
 * @property-read \ManaPHP\Http\CookiesInterface         $cookies
 * @property-read \ManaPHP\Http\RouterInterface          $router
 * @property-read \ManaPHP\Http\DispatcherInterface      $dispatcher
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Http\InvokerInterface         $invoker
 */
class Controller extends Component implements LogCategorizable
{
    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Controller');
    }

    public function invoke(string $action): mixed
    {
        return $this->invoker->invoke($this, $action . 'Action');
    }

    public function getAcl(): array
    {
        return [];
    }

    public function getVerbs(): array
    {
        return [
            'index'    => 'GET',
            'list'     => 'GET',
            'detail'   => 'GET',
            'captcha'  => 'GET',
            'create'   => 'POST',
            'update'   => 'POST',
            'edit'     => 'POST',
            'save'     => 'POST',
            'delete'   => ['DELETE', 'POST'],
            'enable'   => 'POST',
            'disable'  => 'POST',
            'active'   => 'POST',
            'inactive' => 'POST',
        ];
    }

    /**
     * @return array =[$field=>[60,'burst'=>3],'*'=>'','index'=>'','list'=>'','detail'=>'','captcha'=>'',
     *               'create'=>'','update'=>'','edit'=>'', 'save'=>'','delete'=>'']
     */
    public function getRateLimit(): array
    {
        return [];
    }

    /**
     * @return array =[$field => ["etag", "max-age"=>1, "Cache-Control"=>"private, max-age=0, no-store, no-cache,
     *               must-revalidate"]]
     */
    public function getHttpCache(): array
    {
        return [];
    }

    /**
     * @return array =['*'=>'', 'index'=>'','list'=>'','detail'=>'','captcha'=>'',
     *               'create'=>'','update'=>'','edit'=>'', 'save'=>'','delete'=>'']
     */
    public function getPageCache(): array
    {
        return [];
    }
}
