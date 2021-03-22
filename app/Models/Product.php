<?php

namespace App\Models;

class Product extends Model
{
    protected $table = 'product';
    protected $fieldConf = [
        'entityProducts' => [
            'has-many' => [EntityProductSell::class, 'productId']
        ]
    ];
}