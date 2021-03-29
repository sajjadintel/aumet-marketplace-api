<?php

namespace App\Models\Views;

use App\Models\Model;
use App\Models\EntityProductAccountWishlist;

class EntityProductSell extends Model
{
    protected $table = 'vwEntityProductSell';
    protected $fieldConf = [
        'wishlist' => [
            'has-many' => [EntityProductAccountWishlist::class, 'entityProductId'],
        ],
        'bonusConfig' => [
            'type' => self::DT_JSON,
        ]
    ];

    public function findByProductId($productId)
    {
        $self = $this->findone(['productId = ?', $productId]);
        return $self ? $self : $this;
    }
}
