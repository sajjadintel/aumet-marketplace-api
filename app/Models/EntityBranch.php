<?php

namespace App\Models;

class EntityBranch extends Model
{
    protected $table = 'entityBranch';
    protected $fieldConf = [
        'entityId' => [
            'belongs-to-one' => Entity::class,
        ],
        'cityId' => [
            'belongs-to-one' => City::class,
        ],
    ];
}