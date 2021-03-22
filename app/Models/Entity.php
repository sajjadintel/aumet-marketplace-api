<?php

namespace App\Models;

use DB\SQL\Schema;

class Entity extends Model
{
    protected $table = 'entity';
    protected $fieldConf = [
        'accounts' => [
            'has-many' => [Account::class, 'entityId']
        ],
        'countryId' => [
            'belongs-to-one' => Country::class,
        ],
        'currencyId' => [
            'belongs-to-one' => Currency::class,
        ],
        'statusId' => [
            'belongs-to-one' => EntityStatus::class
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
        'image' => [
            'type' => Schema::DT_VARCHAR512,
        ],
        'insertDateTime' => [
            'type' => Schema::DT_DATETIME,
        ],
    ];
}
