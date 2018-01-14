<?php
namespace App\Api\Controllers;

use App\Api\Models\Customer;

class CustomerController extends ControllerBase
{
    public function listAction()
    {
        return $this->response->setJsonContent(Customer::paginate($this->request->get('filters', null, [])));
    }

    public function createAction()
    {
        $first_name = $this->request->get('first_name', 'maxLength:10');
        $last_name = $this->request->get('last_name', 'maxLength:12');

        if (Customer::exists(['first_name' => $first_name, 'last_name' => $last_name])) {
            return $this->response->setJsonContent("CREATE FAILED: `$first_name-$last_name` customer is exists already.");
        } else {
            $customer = new Customer();
            $customer->first_name = $first_name;
            $customer->last_name = $last_name;

            $customer->create();

            return $this->response->setJsonContent(0);
        }
    }

    public function detailAction($id)
    {
        $customer = Customer::findFirst((int)$id);
        if ($customer) {
            return $this->response->setJsonContent($customer->toArray());
        } else {
            return $this->response->setJsonContent("DETAIL FAILED: `[$id]` customer is not exists");
        }
    }

    public function updateAction($id)
    {
        $customer = Customer::findFirst((int)$id);
        if ($customer) {
            return $this->response->setJsonContent($customer->toArray());
        } else {
            return $this->response->setJsonContent("UPDATE FAILED: `[$id]` customer is not exists");
        }
    }

    public function deleteAction($id)
    {
        $customer = Customer::findFirst((int)$id);
        if ($customer) {
            //$customer->delete();
            return $this->response->setJsonContent(0);
        } else {
            return $this->response->setJsonContent("DELETE FAILED: `[$id]` customer is not exists");
        }
    }
}