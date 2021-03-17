<?php


namespace App\Models;


class User extends Model
{
    protected $table = 'user';

    protected $fieldConf = [
        'userAccounts' => [
            'has-many' => [UserAccount::class, 'userId']
        ],
    ];
}