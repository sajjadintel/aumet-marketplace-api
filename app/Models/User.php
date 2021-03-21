<?php

namespace App\Models;

use DB\CortexCollection;

class User extends Model
{
    protected $table = 'user';

    protected $fieldConf = [
        'userAccounts' => [
            'has-many' => [UserAccount::class, 'userId']
        ],
        'accounts' => [
            'has-many' => [Account::class, 'users', 'userAccount', 'relField' => 'userId']
        ],
    ];

    public function pharmacies()
    {
        $pharmacies = $this->pluck($this->accounts, 'entityId', 'typeId', EntityType::TYPE_PHARMACY);
        $pharmaciesCollection = new CortexCollection;
        $pharmaciesCollection->setModels($pharmacies);
        return $pharmaciesCollection;
    }
}