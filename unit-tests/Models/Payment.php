<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:04
 */
namespace Models;

use ManaPHP\Mvc\Model;

class Payment extends Model
{
    public $payment_id;
    public $customer_id;
    public $staff_id;
    public $rental_id;
    public $amount;
    public $payment_date;
    public $last_update;

    public function getSource()
    {
        return 'payment';
    }
}