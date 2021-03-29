<?php

namespace App\Models\Views;

use App\Models\Model;
use App\Models\EntityProductAccountWishlist;
use Constants;

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
        
        if ($self === false) {
            $self = $this;
            $self->errors[] = ['product_id' => "Product {$productId} doesn\'t have an entityProduct Entry"];
            $self->response['statusCode'] = Constants::HTTP_NOT_FOUND;
            $self->response['message'] = 'Not Found';
        }
        
        return $self;
    }
}
