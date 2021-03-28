<?php

class ApiRequestsLog
{

    public static function logRequest($f3, $db, $userId, $sessionId, $data, $ip)
    {
        $dbLog = new GenericModel($db, "apiRequestLog");

        $dbLog->userId = $userId;
        $dbLog->sessionId = $sessionId;
        $dbLog->type = Constants::LOG_TYPE_INIT;
        $dbLog->request =  $f3->get('PARAMS.0');
        $dbLog->data = $data;
        $dbLog->ip = $ip;

        $dbLog->addReturnID();

        return $dbLog;
    }

    public static function updateRequestResponse($dbRequest, $type, $response)
    {
        $dbRequest->type = $type;
        $dbRequest->response = $response;
        $dbRequest->updatedAt = date('Y-m-d H:i:s');

        $dbRequest->update();
    }
}
