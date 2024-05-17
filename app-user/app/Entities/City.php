<?php
declare(strict_types=1);

namespace App\Entities;

/**
 * @property-read Country $country
 */
class City extends Entity
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;
}