<?php

class AppController extends MainController
{
    function beforeRoute()
    {
        $this->beforeRouteFunction();
    }

    public function getAppDetails()
    {
        $res = new stdClass();
        $res->primaryColor = getenv('APP_PRIMARY_COLOR');
        $res->secondaryColor = getenv('APP_SECONDARY_COLOR');
        $res->appName = getenv("APP_NAME");
        $res->appVersion = getenv("APP_VERSION");

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_appSettings')), $res);
    }
}
