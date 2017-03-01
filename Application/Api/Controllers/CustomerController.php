<?php
namespace Application\Api\Controllers;

use Application\Api\Models\Customer;

class CustomerController extends ControllerBase
{
    public function listAction()
    {
        $customers = Customer::findAll(['', 'limit' => 10, 'offset' => 10 * $this->request->getQuery('page', 'int', 1)]);

        return $this->response->setJsonContent(['code' => 0, 'message' => '', 'data' => ['customers' => $customers]]);
    }

    public function createAction()
    {
        $first_name = $this->request->get('first_name', 'maxLength:10');
        $last_name = $this->request->get('last_name', 'maxLength:12');

        if (Customer::exists(['first_name' => $first_name, 'last_name' => $last_name])) {
            return $this->response->setJsonContent(['code' => __LINE__, 'message' => "CREATE FAILED: `$first_name-$last_name` customer is exists already."]);
        } else {
            $customer = new Customer();
            $customer->first_name = $first_name;
            $customer->last_name = $last_name;

            //$customer->create();

            return $this->response->setJsonContent(['code' => 0, 'message' => '']);
        }
    }

    public function detailAction($id)
    {
        $customer = Customer::findFirst((int)$id);
        if ($customer) {
            return $this->response->setJsonContent(['code' => 0, 'message' => '', 'data' => $customer->toArray()]);
        } else {
            return $this->response->setJsonContent(['code' => __LINE__, 'message' => "DETAIL FAILED: `[$id]` customer is not exists"]);
        }
    }

    public function updateAction($id)
    {
        $customer = Customer::findFirst((int)$id);
        if ($customer) {
            return $this->response->setJsonContent(['code' => 0, 'message' => 'UPDATE SUCCESS', 'data' => $customer->toArray()]);
        } else {
            return $this->response->setJsonContent(['code' => __LINE__, 'message' => "UPDATE FAILED: `[$id]` customer is not exists"]);
        }
    }

    public function deleteAction($id)
    {
        $customer = Customer::findFirst((int)$id);
        if ($customer) {
            //$customer->delete();
            return $this->response->setJsonContent(['code' => 0, 'message' => 'DELETE SUCCESS']);
        } else {
            return $this->response->setJsonContent(['code' => __LINE__, 'message' => "DELETE FAILED: `[$id]` customer is not exists"]);
        }
    }
}