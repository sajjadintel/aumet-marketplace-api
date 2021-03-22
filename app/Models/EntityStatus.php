<?php

namespace App\Models;

use DB\SQL\Schema;

class EntityStatus extends Model
{
    protected $table = 'entityStatus';
    protected $fieldConf = [
        'entities' => [
            'has-many' => [Entity::class, 'statusId']
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
    ];
}