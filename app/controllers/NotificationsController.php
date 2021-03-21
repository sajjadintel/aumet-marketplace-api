<?php

use App\Models\Notification;
use App\Models\User;
use App\Resources\NotificationResource;

class NotificationsController extends MainController
{
    public function index()
    {
        $page = (int) ($this->f3->get('GET.page') ?? 1);
        $pageSize = (int) ($this->f3->get('GET.size') ?? 10);
        $notifications = new Notification;
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            NotificationResource::collection(
                $notifications->paginateByUser($page, $pageSize, $this->objUser->id)
            )
        );
    }
}