<?php

class ApiRequestsLog
{

    public static function logRequest($f3, $db, $userId, $data, $type, $ip)
    {

        $dbLog = new GenericModel($db, "apiRequestLog");

        $dbLog->userId = $userId;
        $dbLog->type = $type;
        $dbLog->request =  $f3->get('PARAMS.0');
        $dbLog->data = $data;
        $dbLog->ip = $ip;

        // TODO: Add error handling
        $dbLog->add();
    }
}
