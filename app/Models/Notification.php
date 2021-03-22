<?php

namespace App\Models;

use Constants;

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

    public function markAsRead($id, $userId)
    {
        $this->load(['id = ?', $id]);
        if ($this->dry()) {
            $this->response['statusCode'] = Constants::HTTP_NOT_FOUND;
            $this->response['message'] = 'Not found';
            $this->errors = ['notification_id' => $this->response['message']];
            return $this;
        }

        if ($this->user_id->id !== $userId) {
            $this->response['statusCode'] = Constants::HTTP_FORBIDDEN;
            $this->response['message'] = 'You are not authorized for this action';
            $this->errors = ['notification_id' => $this->response['message']];
            return $this;
        }

        $this->read = true;
        return $this->save();
    }
}