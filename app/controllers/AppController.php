<?php

use Ahc\Jwt\JWT;

class AppController extends MainController
{
    function beforeRoute()
    {
        $this->beforeRouteFunction();
    }

    public function getAppDetails()
    {
        $payload = array(
            'userId' => '9',
            'userEmail' => "antoineaboucherfane@gmail.com",
            'fullName' => "Antoine Abou Cherfane"
        );

        $jwt = new JWT(MainController::JWTSecretKey, 'HS256', (86400 * 30), 10);
        $jwtSignedKey = $jwt->encode($payload);

        $userSession = new GenericModel($this->db, 'userSession');

        $userSession->userId = '9';
        $userSession->token = $jwtSignedKey;
        $userSession->deviceType = "undefined";

        // $userSession->add();

        $res = new stdClass();
        $res->primaryColor = getenv('APP_PRIMARY_COLOR');
        $res->secondaryColor = getenv('APP_SECONDARY_COLOR');
        $res->appName = getenv("APP_NAME");
        $res->appVersion = getenv("APP_VERSION");
        $res->jwtSignedKey = $jwtSignedKey;

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
