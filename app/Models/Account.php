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
    ];
}