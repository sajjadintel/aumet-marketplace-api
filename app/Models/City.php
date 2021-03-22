<?php

namespace App\Models;

class City extends Model
{
    protected $table = 'city';
    protected $fieldConf = [
        'countryId' => [
            'belongs-to-one' => Country::class,
        ],
        'entityBranches' => [
            'has-many' => [EntityBranch::class, 'cityId'],
        ]
    ];
}