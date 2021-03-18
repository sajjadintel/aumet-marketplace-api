<?php

namespace App\Models;

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

        return $pharmacies ? $pharmacies : [];
    }
}