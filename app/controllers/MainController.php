<?php

use Ahc\Jwt\JWT;

class MainController
{
    const APIKey = "zTvkXwJSSRa5DVvTgQhaUW52DkpkeSz";
    const JWTSecretKey = "mxczKngV84P/26qs+}nrj!T>RD^5^3F=";

    protected $f3;
    protected $db;

    protected $isAuth;

    protected $accessTokenPayload;

    protected $objUser;
    protected $objEntityList;

    // header values being used
    protected $accessToken;
    protected $apiKey;
    protected $language;
    protected $sessionId;

    // header values not being used
    protected $deviceType;
    protected $deviceId;
    protected $mnc;
    protected $mcc;

    protected $requestData;

    function beforeRoute()
    {
        $this->beforeRouteFunction();
        $this->validateUser();
    }

    public function beforeRouteFunction()
    {
        if (!$this->verifyApiKey()) {
            $this->f3->error(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.401_invalidApiKey'));
        }

        $this->prepareRequestData();
        $this->verifyUser();
        $this->logRequest(Constants::LOG_TYPE_INIT);
    }

    public function validateUser()
    {
        if (!$this->isAuth) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.401_invalidCredentials'), null);
        }
    }

    function __construct()
    {
        $this->f3 = Base::instance();
        $this->accessToken = null;
        $this->apiKey = null;
        $this->language = null;
        $this->sessionId = null;
        $this->env = getenv('ENV');
        $this->db =  $this->f3->get("dbConnectionMain");

        $this->fetchHttpHeaderValues();
    }

    function fetchHttpHeaderValues()
    {
        $headers = null;

        $headers = getallheaders();

        $headers = Utils::arrayKeysToLowercase($headers);

        // Add language
        array_key_exists("x-user-lang", $headers) ? $this->language = $headers['x-user-lang'] : $this->f3->error(Constants::HTTP_UNSUPPORTED_MEDIA_TYPE, $this->f3->get('RESPONSE.415_missingHeader', $this->f3->get('RESPONSE.entity_language')));
        if (!Utils::stringInEnvArray($this->language, getenv("LANGUAGES_SUPPORTED"))) {
            $this->f3->set('LANGUAGE', 'en');
            $this->f3->error(Constants::HTTP_UNSUPPORTED_MEDIA_TYPE, $this->f3->get('RESPONSE.415_invalidHeader', $this->f3->get('RESPONSE.entity_language'), getenv("LANGUAGES_SUPPORTED")));
        };
        $this->f3->set('LANGUAGE', $this->language);

        // Required Headers
        array_key_exists("x-access-token", $headers) ? $this->accessToken = $headers['x-access-token'] : null;
        array_key_exists("x-api-key", $headers) ? $this->apiKey = $headers['x-api-key'] : $this->f3->error(Constants::HTTP_UNSUPPORTED_MEDIA_TYPE, $this->f3->get('RESPONSE.415_missingHeader', $this->f3->get('RESPONSE.entity_apiKey')));
        array_key_exists("x-session-id", $headers) ? $this->sessionId = $headers['x-session-id'] : $this->f3->error(Constants::HTTP_UNSUPPORTED_MEDIA_TYPE, $this->f3->get('RESPONSE.415_missingHeader', $this->f3->get('RESPONSE.entity_sessionId')));

        // Optional Headers
        if (array_key_exists("x-device-os", $headers)) {
            $this->deviceType = $headers['x-device-os'];
        }

        if (array_key_exists("x-device-id", $headers)) {
            $this->deviceId = $headers['x-device-id'];
        }

        if (array_key_exists("x-device-mnc", $headers)) {
            $this->mnc = $headers['x-device-mnc'];
        }

        if (array_key_exists("x-device-mcc", $headers)) {
            $this->mcc = $headers['x-device-mcc'];
        }
    }

    function prepareRequestData()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        switch ($method) {
            case 'PUT':
            case 'POST':
                $this->requestData = file_get_contents('php://input');
                $this->requestData = empty($this->requestData) ? '[]' : $this->requestData;
                $this->requestData = utf8_encode($this->requestData);
                $this->requestData = json_decode($this->requestData);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->f3->error(Constants::HTTP_UNSUPPORTED_MEDIA_TYPE, $this->f3->get('RESPONSE.415_unsupported', json_last_error()));
                }

                break;
            default:
                break;
        }
    }

    function verifyUser()
    {
        $this->isAuth = false;
        try {
            $jwt = new JWT(MainController::JWTSecretKey, 'HS256', 3600, 10);
            if (isset($this->accessToken) && !is_null($this->accessToken) && $this->accessToken != "") {
                $this->accessTokenPayload = $jwt->decode($this->accessToken);
                if (is_array($this->accessTokenPayload)) {
                    $userId = $this->accessTokenPayload["userId"];
                    if (is_numeric($userId)) {
                        $userCredential = new GenericModel($this->db, 'userSession');
                        $userCredential->load(array('userId = ? AND token = ? and isActive = 1', $userId, $this->accessToken));
                        if (!$userCredential->dry()) {
                            $dbUser = new GenericModel($this->db, 'vwUser');
                            $dbUser->roleName = "roleName_" . $this->language;
                            $dbUser->getWhere("id={$userId}");
                            if (!$dbUser->dry()) {
                                $this->isAuth = true;
                                $this->objUser = $dbUser;


                                $dbEntityList = new GenericModel($this->db, 'vwAccountEntities');
                                $dbEntityList->id = "entityId";
                                $dbEntityList->name = "entityName_" . $this->language;
                                $dbEntityList->getWhere("accountId={$dbUser->accountId}");

                                while (!$dbEntityList->dry()) {
                                    $this->objEntityList[$dbEntityList->id] = $dbEntityList->name;
                                    $dbEntityList->next();
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $ex) {
        }
    }

    function verifyApiKey()
    {
        return $this->apiKey == MainController::APIKey;
    }

    function formatResponse($statusCode = 200, $message = null, $data = null)
    {
        // set the header to make sure cache is forced and treat this as json
        header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
        header('Content-Type: application/json');
        header("Status: 200 OK");
        http_response_code($statusCode);

        return json_encode(array(
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data
        ));
    }

    function sendSuccess($statusCode, $message = null, $data = null)
    {
        $response =  $this->formatResponse($statusCode, $message, $data);
        $this->logRequest(Constants::LOG_TYPE_SUCCESS, $response);
        echo $response;

        die;
    }

    function sendError($statusCode, $message = null, $data = null)
    {
        $response = $this->formatResponse($statusCode, $message, $data);
        $this->logRequest(Constants::LOG_TYPE_ERROR, $response);
        echo $response;

        die;
    }

    function logRequest($type, $data = null)
    {
        // if the $userId == -1 this is an anonymous request (login/sigenup) for example

        $userId = $this->isAuth ? $this->objUser->id : -1;
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        $requestData = $method != 'GET' ? json_encode($this->requestData) : json_encode($this->f3->get('GET'));
        $data = !empty($data) ? $data : $requestData;
        $ip = Utils::getClientIP();

        ApiRequestsLog::logRequest($this->f3, $this->db, $userId, $this->sessionId, $data, $type, $ip);
    }
}
