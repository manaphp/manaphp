<?php
declare(strict_types=1);

namespace App\Entities;

/**
 * @property-read \App\Entities\Address $address
 */
class Customer extends Entity
{
    public $customer_id;
    public $store_id;
    public $first_name;
    public $last_name;
    public $email;
    public $address_id;
    public $active;
    public $create_date;
    public $last_update;
}