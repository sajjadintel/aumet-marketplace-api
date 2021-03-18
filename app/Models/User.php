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
        $entityIds = $this->pluck($this->accounts, 'entityId.id');
        $entity = new Entity;
                
        $pharmacies = $entity->find(['id IN ? AND typeId = ?', $entityIds, EntityType::TYPE_PHARMACY]);
        return $pharmacies ? $pharmacies : [];
    }
}