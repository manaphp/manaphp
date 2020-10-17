<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:04
 */

namespace Tests\MongoDB\Models;

use ManaPHP\Mongodb\Model;

class Payment extends Model
{
    public $payment_id;
    public $customer_id;
    public $staff_id;
    public $rental_id;
    public $amount;
    public $payment_date;
    public $last_update;

    public function getFieldTypes()
    {
        return [
            '_id'          => 'integer',
            'payment_id'   => 'integer',
            'customer_id'  => 'integer',
            'staff_id'     => 'integer',
            'amount'       => 'string',
            'payment_date' => 'string',
            'last_update'  => 'string'
        ];
    }
}
