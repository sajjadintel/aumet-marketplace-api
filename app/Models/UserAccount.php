<?php


namespace App\Models;


use DB\SQL\Schema;

class UserAccount extends Model
{
    protected $table = 'userAccount';

    protected $fieldConf = [
        'userId' => [
            'type' => Schema::DT_INT,
            'belongs-to-one' => User::class
        ],
    ];
}