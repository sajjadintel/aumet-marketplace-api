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
        'cartDetails' => [
            'has-many' => [CartDetail::class, 'userId']
        ],
        'notifications' => [
            'has-many' => [Notification::class, 'user_id'],
        ]
    ];

    public function pharmacies()
    {
        $pharmacies = $this->pluck($this->accounts, 'entityId', 'typeId', EntityType::TYPE_PHARMACY);
        $pharmaciesCollection = new CortexCollection;
        $pharmaciesCollection->setModels($pharmacies);
        return $pharmaciesCollection;
    }

    /**
     * Return saved for later items for a certain account, if an account
     * is not given, the first account is used
     *
     * @param int $accountId
     * @return DB\CortexCollection
     */
    public function wishlist($accountId = null)
    {
        if ($accountId) {
            $account = new Account;
            $account->id = $accountId;
        } else {
            $userAccount = new UserAccount;
            $userAccount->load(['userId = ?', $this->id]);
            $account = $userAccount->accountId;
        }

        return $account->wishlist;
    }
}