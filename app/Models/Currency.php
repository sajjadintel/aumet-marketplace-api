<?php

namespace App\Models;

use DB\SQL\Schema;

class Currency extends Model
{
    protected $table = 'currency';
    protected $fieldConf = [
        'entities' => [
            'has-many' => [Entity::class, 'currencyId'],
        ],
        'id' => [
            'type' => Schema::DT_INT,
        ],
        'name' => [
            'type' => Schema::DT_VARCHAR128,
        ],
        'short_name' => [
            'type' => Schema::DT_VARCHAR128,
        ],
        'symbol' => [
            'type' => Schema::DT_VARCHAR128,
        ],
        'conversion_to_usd' => [
            'type' => Schema::DT_DECIMAL,
        ],
    ];
}
