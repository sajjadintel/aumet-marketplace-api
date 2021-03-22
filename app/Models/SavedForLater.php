<?php

namespace App\Models;

use App\Models\Views\EntityProductSell;
use Constants;
use DB\SQL\Schema;

class SavedForLater extends Model
{
    protected $table = 'accountEntityProductSavedForLater';
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
        $data = $this->initializeMissingKeys($data);
        if (array_key_exists('cart_detail_id', $data)) {
            $cartDetail = new CartDetail;
            $cartDetail->load(['id = ? AND accountId = ?', $data['cart_detail_id'], $data['account_id']]);
            if ($cartDetail->dry()) {
                $this->errors[] = ['cart_detail_id' => 'Does not exist'];
                $this->response['statusCode'] = Constants::HTTP_BAD_REQUEST;
                $this->response['message'] = 'Validation Failed';
                return $this;
            }
            
            $data['entity_product_id'] = $cartDetail->entityProductId->id;
            $data['quantity'] = $cartDetail->quantity;
        }

        // created for composite unique contraint validation
        $data['entity_product_id_account_id'] = [
            'entityProductId' => $data['entity_product_id'],
            'accountId' => $data['account_id']
        ];

        if ($this->check($data) !== true) {
            $this->response['statusCode'] = Constants::HTTP_BAD_REQUEST;
            $this->response['message'] = 'Validation Failed';
            return $this;
        }

        if (isset($cartDetail)) {
            $cartDetail->erase();
        }

        $this->accountId = $data['account_id'];
        $this->entityProductId = $data['entity_product_id'];
        $this->quantity = $data['quantity'];

        return $this->save();
    }

    public function retrieveAndCheckForAccount($accountId)
    {
        $this->load(['id = ?', $this->id]);
        if ($this->dry()) {
            $this->errors[] = ['id' => 'Element does not exist'];
            $this->response['statusCode'] = Constants::HTTP_NOT_FOUND; 
            $this->response['message'] = 'Not Found';
            return $this;
        }

        if ($this->accountId->id !== $accountId) {
            $this->errors[] = ['id' => 'You are not allowed to do this action'];
            $this->response['statusCode'] = Constants::HTTP_FORBIDDEN; 
            $this->response['message'] = 'Forbidden';
            return $this;
        }

        return $this;
    }

    public function getRules()
    {
        return [
            'account_id' => 'required|numeric|exists,id,account',
            'entity_product_id' => 'required|numeric|exists,id,entityProductSell',
            'entity_product_id_account_id' => "composite_unique,{$this->table}",
            'quantity' => 'numeric',
        ];
    }
}