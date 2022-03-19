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

    public function createAction()
    {
        $first_name = $this->request->get('first_name');
        $last_name = $this->request->get('last_name');

        if (Customer::exists(['first_name' => $first_name, 'last_name' => $last_name])) {
            return "CREATE FAILED: `$first_name-$last_name` customer is exists already.";
        } else {
            $customer = new Customer();
            $customer->first_name = $first_name;
            $customer->last_name = $last_name;

            return $customer->create();
        }
    }

    public function detailAction()
    {
        return Customer::rGet();
    }

    public function updateAction()
    {
        return Customer::rUpdate();
    }

    public function deleteAction()
    {
        return Customer::rDelete();
    }
}