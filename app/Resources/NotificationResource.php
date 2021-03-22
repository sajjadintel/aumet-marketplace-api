<?php

namespace App\Resources;

class NotificationResource extends JsonResource
{
    public static function format($notification)
    {
        return [
            'id' => $notification->id,
            'user_id' => $notification->user_id->id,
            'title' => $notification->title,
            'body' => $notification->body,
            'created_at' => $notification->created_at,
            'read' => (bool) $notification->read,
        ];
    }
}