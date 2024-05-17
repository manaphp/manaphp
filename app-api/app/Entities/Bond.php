<?php
declare(strict_types=1);

namespace App\Entities;

class Bond extends Entity
{
    public $bond_id;
    public $bond_name;
    public $bond_pinyin;
    public $increase_rate;
    public $price;
    public $volume;
    public $yesterday_price;
    public $amount_left;
    public $double_low;
    public $pb;
    public $stock_id;
    public $stock_name;
    public $stock_pinyin;
    public $stock_price;
    public $stock_increase_rate;
    public $stock_volume;
    public $convert_price;
    public $convert_value;
    public $premium_rate;
    public $market_cd;
    public $year_left;
    public $ytm_rate;
    public $updated_time;
    public $updated_date;
}