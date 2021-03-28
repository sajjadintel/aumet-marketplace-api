<?php

use App\Models\Notification;
use App\Resources\NotificationResource;

class NotificationsController extends MainController {
    public function index()
    {
        $page = (int)($this->f3->get('GET.page') ?? 1);
        $pageSize = (int)($this->f3->get('GET.size') ?? 10);
        $notifications = new Notification;
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            NotificationResource::collection(
                $notifications->paginateByUser($page, $pageSize, $this->objUser->id)
            )
        );
    }

    public function markAsRead()
    {
        $notification = new Notification;
        $notification = $notification->markAsRead($this->f3->get('PARAMS.id'), $this->objUser->id);
        if ($notification->hasErrors()) {
            return $this->sendError(
                $notification->response['statusCode'],
                $notification->response['message'],
                $notification->errors
            );
        }

        return $this->sendSuccess(
            $notification->response['statusCode'],
            $notification->response['message'],
            NotificationResource::format($notification)
        );
    }

    public function postSupport()
    {
        if (!isset($this->requestData->message))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_message')), null);
        $message = $this->requestData->message;

        if (!isset($this->requestData->subject))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_subject')), null);
        $subject = $this->requestData->subject;

        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $entityId = $arrEntityId[0];

        $supportLog = new GenericModel($this->db, "supportLog");
        $supportLog->entityId = $entityId;
        $supportLog->userId = $this->objUser->id;
        $supportLog->email = $this->objUser->email;
        $supportLog->message = $message;
        $supportLog->subject = $subject;
        $supportLog->typeId = 1;
        $supportLog->entityBuyerId = NULL;
        $supportLog->supportReasonId = NULL;
        $supportLog->phone = NULL;
        $supportLog->orderId = NULL;
        $supportLog->requestCall = NULL;
        $supportLog->add();

        NotificationHelper::customerSupportNotification($this->f3, $this->db, $supportLog);
        NotificationHelper::customerSupportConfirmNotification($this->f3, $this->db, $supportLog);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_supportMessage')), null);
    }

}