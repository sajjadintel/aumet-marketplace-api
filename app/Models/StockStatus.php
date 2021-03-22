<?php

namespace App\Models;

class StockStatus extends Model
{
    protected $table = 'stockStatus';
    protected $fieldConf = [
        'entityProducts' => [
            'has-many' => [EntityProductSell::class, 'stockStatusId']
        ]
    ];
}