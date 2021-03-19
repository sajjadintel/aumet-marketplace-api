<?php

namespace App\Models;

class EntityProductSell extends Model
{
    protected $table = 'entityProductSell';
    protected $fieldConf = [
        'cartDetails' => [
            'has-many' => [CartDetail::class, 'entityProductId'],
        ],
        'savedForLater' => [
            'has-many' => [SavedForLater::class, 'entityProductId'],
        ]
    ];
}