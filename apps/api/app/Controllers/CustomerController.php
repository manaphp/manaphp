<?php
namespace App\Controllers;

use App\Models\Customer;

class CustomerController extends ControllerBase
{
    public function indexAction()
    {
        return $this->response->setJsonContent(Customer::paginate($this->request->get('filters', [])));
    }

    public function createAction()
    {
        $first_name = $this->request->get('first_name');
        $last_name = $this->request->get('last_name');

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
        return $this->response->setJsonContent(Customer::firstOrFail($id, '*', ['with' => ['address']]));
    }

    public function updateAction($id)
    {
        $customer = Customer::firstOrFail($id);

        return $this->response->setJsonContent($customer);
    }

    public function deleteAction($id)
    {
        $customer = Customer::first((int)$id);
        if ($customer) {
            //$customer->delete();
            return $this->response->setJsonContent(0);
        } else {
            return $this->response->setJsonContent("DELETE FAILED: `[$id]` customer is not exists");
        }
    }
}