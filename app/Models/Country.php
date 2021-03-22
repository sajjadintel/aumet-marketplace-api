<?php

namespace App\Models;

use DB\SQL\Schema;

class Country extends Model
{
    protected $table = 'country';
    protected $fieldConf = [
        'entities' => [
            'has-many' => [Entity::class, 'countryId'],
        ],
        'cities' => [
            'has-many' => [City::class, 'countryId'],
        ],
        'id' => [
            'type' => Schema::DT_INT,
        ],
        'name_en' => [
            'type' => Schema::DT_VARCHAR128,
        ],
        'name_ar' => [
            'type' => Schema::DT_VARCHAR128,
        ],
        'name_fr' => [
            'type' => Schema::DT_VARCHAR128,
        ],
        'currency' => [
            'type' => Schema::DT_VARCHAR128,
        ],
        'is_registered_from' => [
            'type' => Schema::DT_TINYINT,
        ],
        'code' => [
            'type' => Schema::DT_VARCHAR128,
        ],
    ];
}
