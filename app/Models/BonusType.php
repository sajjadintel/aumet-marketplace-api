<?php

namespace App\Models;

class BonusType extends Model
{
    protected $table = 'bonusType';
    protected $fieldConf = [
        'entityProductBonusDetails' => [
            'has-many' => [EntityProductSellBonusDetail::class, 'bonusTypeId']
        ],
    ];
}