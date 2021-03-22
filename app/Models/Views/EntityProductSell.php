<?php

namespace App\Models\Views;

use App\Models\Model;
use App\Models\SavedForLater;

class EntityProductSell extends Model
{
    protected $table = 'vwEntityProductSell';
    protected $fieldConf = [
        'savedForLater' => [
            'has-many' => [SavedForLater::class, 'entityProductId'],
        ],
        'bonusConfig' => [
            'type' => self::DT_JSON,
        ]
    ];
}
