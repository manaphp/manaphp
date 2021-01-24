<?php

namespace App\Models;

/**
 * Class Country
 *
 * @property-read City $cities
 */
class Country extends Model
{
    public $country_id;
    public $country;
    public $last_update;

    /**
     * @return string
     */
    public function table()
    {
        return 'country';
    }

    /**
     * @return string
     */
    public function primaryKey()
    {
        return 'country_id';
    }
}