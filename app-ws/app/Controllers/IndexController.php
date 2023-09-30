<?php

namespace App\Controllers;

class IndexController extends Controller
{
    public function openAction($fd)
    {
//        $data = [];
//        $data['admin_id'] = $this->identity->getId();
//        $data['admin_name'] = $this->identity->getName();
//        $data['role'] = $this->identity->getRole();
//
//        $token = jwt_encode($data, $ttl, 'pusher.admin');

        $token = $this->request->getToken();
        $this->identity->set(jwt_decode($token, 'pusher.admin'));
    }

    public function closeAction($fd)
    {

    }

    public function messageAction($data)
    {

    }
}
