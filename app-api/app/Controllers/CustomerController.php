<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Customer;

class CustomerController extends Controller
{
    public function indexAction()
    {
        return Customer::paginate($this->request->get('filters', []));
    }

    public function createAction(Customer $customer)
    {
        return $customer->create();
    }

    public function detailAction(Customer $customer)
    {
        return $customer;
    }

    public function updateAction(Customer $customer)
    {
        return $customer->update();
    }

    public function deleteAction(Customer $customer)
    {
        return $customer->delete();
    }
}