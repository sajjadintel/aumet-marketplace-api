<?php

namespace App\Models;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $fieldConf = [
        'user_id' => [
            'belongs-to-one' => User::class,
        ],
    ];

    public function paginateByUser($page = 1, $pageSize = 10, $userId)
    {
        return $this->paginate(
            $page - 1, 
            $pageSize, 
            ['user_id = ?', $userId],
            ['order' => 'created_at DESC']
        )['subset'];
    }
}