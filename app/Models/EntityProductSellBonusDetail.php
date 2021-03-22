<?php

namespace App\Models;

class EntityProductSellBonusDetail extends Model
{
    protected $table = 'entityProductSellBonusDetail';
    protected $fieldConf = [
        'entityProductId' => [
            'belongs-to-one' => EntityProductSell::class,
        ],
        'bonusTypeId' => [
            'belongs-to-one' => BonusType::class,
        ]
    ];
}