<?php

class AppController extends MainController {
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

//        $dbSetting = new GenericModel($this->db, "setting");
//        $settings = $dbSetting->findWhere("language = '{$this->language}'");
//        $res->settings = $settings;

        $dbSetting = new GenericModel($this->db, "setting");
        $dbSetting->getWhere("language = '{$this->language}'");

        while (!$dbSetting->dry()) {
            $res->settings[$dbSetting->title] = $dbSetting->value;
            $dbSetting->next();
        }


        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_appSettings')), $res);
    }

    public function getMenu()
    {
        $this->validateUser();
        $res = Utils::getMenuById($this->f3, $this->db, $this->objUser->menuId, $this->language, 0);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_menuItems')), $res);
    }

    public function getMenuSection()
    {
        $this->validateUser();
        if (!isset($_GET['parentItemId']) || !is_numeric(($_GET['parentItemId']))) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_parentItemId')), null);
        }
        $parentItemId = $_GET['parentItemId'];

        $res = Utils::getMenuById($this->f3, $this->db, $this->objUser->menuId, $this->language, $parentItemId);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_menuItems')), $res);
    }
}
