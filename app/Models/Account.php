<?php

namespace App\Models;

use DB\SQL\Schema;

class Account extends Model
{
    protected $table = 'account';
    protected $fieldConf = [
        'users' => [
            'has-many' => [User::class, 'accounts', 'userAccount', 'relField' => 'accountId']
        ],
        'entityId' => [
            'type' => Schema::DT_INT,
            'belongs-to-one' => Entity::class
        ],
        'savedForLater' => [
            'has-many' => [SavedForLater::class, 'accountId'],
        ],
        'cartDetails' => [
            'has-many' => [CartDetail::class, 'accountId'],
        ]
    ];

    /**
     * Returns the country object of the account's entity
     *
     * @return Country|null
     */
    public function country()
    {
        return $this->entityId->countryId;
    } 
}