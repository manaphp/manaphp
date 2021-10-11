<?php

namespace ManaPHP\Http;

/**
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Http\ResponseInterface        $response
 * @property-read \ManaPHP\Http\RouterInterface          $router
 * @property-read \ManaPHP\Http\DispatcherInterface      $dispatcher
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 */
class Controller extends \ManaPHP\Controller
{
    /**
     * @return array
     */
    public function getAcl()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getVerbs()
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
    public function getRateLimit()
    {
        return [];
    }

    /**
     * @return array =[$field => ["etag", "max-age"=>1, "Cache-Control"=>"private, max-age=0, no-store, no-cache,
     *               must-revalidate"]]
     */
    public function getHttpCache()
    {
        return [];
    }

    /**
     * @return array =['*'=>'', 'index'=>'','list'=>'','detail'=>'','captcha'=>'',
     *               'create'=>'','update'=>'','edit'=>'', 'save'=>'','delete'=>'']
     */
    public function getPageCache()
    {
        return [];
    }
}
