<?php

namespace App\Models;

use Constants;

class CartDetail extends Model
{
    protected $table = 'cartDetail';
    protected $fieldConf = [
        'entityProductId' => [
            'belongs-to-one' => EntityProductSell::class,
        ],
        'accountId' => [
            'belongs-to-one' => Account::class,
        ],
        'userId' => [
            'belongs-to-one' => User::class,
        ]
    ];

    public function create($data)
    {
        if ($this->check($data) !== true) {
            $this->response['statusCode'] = Constants::HTTP_BAD_REQUEST;
            $this->response['message'] = 'Validation Failed';
            return $this;
        }
    }

    public function getRules()
    {
        return [
            'userId' => 'required|numeric|exists,id,user',
            'accountId' => 'required|numeric|exists,id,account',
            'entityProductId' => 'required|numeric|exists,id,entityProductSell',
            'quantity' => 'numeric',
        ];
    }
}
