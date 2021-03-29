<?php

namespace App\Models;

use App\Models\Views\EntityProductSell;
use Constants;
use DB\SQL\Schema;

class EntityProductAccountWishlist extends Model
{
    protected $table = 'entityProductAccountWishlist';
    protected $fieldConf = [
        'id' => [
            'type' => Schema::DT_INT,
        ],
        'accountId' => [
            'type' => Schema::DT_INT,
            'belongs-to-one' => Account::class,
        ],
        'entityProductId' => [
            'type' => Schema::DT_INT,
            'belongs-to-one' => EntityProductSell::class,
        ],
        'quantity' => [
            'type' => Schema::DT_INT,
            'validate' => 'numeric'
        ],
        'createdAt' => [
            'type' => Schema::DT_DATETIME,
        ],
    ];

    public function create($data)
    {
        $this->validate($data);
        if ($this->hasErrors()) {
            return $this;
        }

        $entityProduct = (new EntityProductSell)->findByProductId($data['product_id']);
        if ($entityProduct->hasErrors()) {
            $this->errors = $entityProduct->errors;
            $this->response['statusCode'] = $entityProduct->response['statusCode'];
            $this->response['message'] = $entityProduct->response['message'];
            return $this;
        }

        $data['entity_product_id'] = $entityProduct->id;

        // created for composite unique contraint validation
        $data['entity_product_id_account_id'] = [
            'entityProductId' => $data['entity_product_id'],
            'accountId' => $data['account_id']
        ];

        $this->validate($data);
        if ($this->hasErrors()) {
            return $this;
        }

        $this->accountId = $data['account_id'];
        $this->entityProductId = $data['entity_product_id'];

        return $this->save();
    }

    public function retrieveAndCheckForAccount($accountId, $productId)
    {
        $data = ['account_id' => $accountId, 'product_id' => $productId];
        $this->validate($data);
        if ($this->hasErrors()) {
            return $this;
        }

        $entityProduct = (new EntityProductSell)->findByProductId($productId);
        if ($entityProduct->hasErrors()) {
            $this->errors = $entityProduct->errors;
            $this->response['statusCode'] = $entityProduct->response['statusCode'];
            $this->response['message'] = $entityProduct->response['message'];
            return $this;
        }

        $this->load(['accountId = ? AND entityProductId = ?', $accountId, $entityProduct->id]);
        if ($this->dry()) {
            $this->errors[] = ['product_id' => "Product {$data['product_id']} is not in wishlist"];
            $this->response['statusCode'] = Constants::HTTP_NOT_FOUND; 
            $this->response['message'] = 'Not Found';
            return $this;
        }

        return $this;
    }

    public function getRules()
    {
        return [
            'account_id' => 'required|numeric',
            'product_id' => 'required|numeric|exists,id,product',
            'entity_product_id_account_id' => "composite_unique,{$this->table}",
            'quantity' => 'numeric',
        ];
    }
}