<?php

use Ahc\Jwt\JWT;
use SendGrid\Mail\Content;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Firebase\Auth\Token\Exception\InvalidToken;

class UserController extends MainController
{
    function beforeRoute()
    {
        $this->beforeRouteFunction();
    }

    function postSignUp()
    {
        if (!isset($this->requestData->email))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_email')), null);
        $email = $this->requestData->email;

        if (!isset($this->requestData->password))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_password')), null);
        $password = $this->requestData->password;

        if (!isset($this->requestData->pharmacyName) && !isset($this->requestData->distributorName))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_name')), null);
        $entityName = !empty($this->requestData->pharmacyName) ? $this->requestData->pharmacyName : $this->requestData->distributorName;

        if (!isset($this->requestData->country))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_countryId')), null);
        $countryId = $this->requestData->country;

        if (!isset($this->requestData->city))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_cityId')), null);
        $cityId = $this->requestData->city;

        if (!isset($this->requestData->address))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_address')), null);
        $address = $this->requestData->address;

        if (!isset($this->requestData->mobile))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_mobile')), null);
        $mobile = $this->requestData->mobile;

        $uid = $this->requestData->uid ?? NULL;
        $name = $this->requestData->name ?? NULL;
        $tradeLicenseNumber = $this->requestData->tradeLicenseNumber ?? NULL;
        $pharmacyDocument = $this->requestData->pharmacyDocument ?? NULL;
        $isDistributor = empty($this->requestData->pharmacyName);

        // Check if email is unique
        $dbUser = new GenericModel($this->db, "user");
        $dbUser->getByField("email", $email);

        if (!$dbUser->dry()) {
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_alreadyExists', $this->f3->get('RESPONSE.entity_email')), null);
        }

        // Check if phone number is unique
        $dbUser = new GenericModel($this->db, "user");
        $dbUser->getByField("mobile", $mobile);

        if (!$dbUser->dry()) {
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_alreadyExists', $this->f3->get('RESPONSE.entity_mobile')), null);
        }

        // Check if trading license is unique
        $dbEntityBranch = new GenericModel($this->db, "entityBranch");
        if ($tradeLicenseNumber != '') {
            $dbEntityBranch->getByField("tradeLicenseNumber", $tradeLicenseNumber);

            if (!$dbEntityBranch->dry()) {
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_alreadyExists', $this->f3->get('RESPONSE.entity_trading_license')), null);
            }
        }

        // Get currency symbol
        $dbCountry = new GenericModel($this->db, "country");
        $country = $dbCountry->getById($countryId)[0];
        $currencySymbol = $country['currency'];

        // Get currency id
        $dbCurrency = new GenericModel($this->db, "currency");
        $currency = $dbCurrency->getByField("symbol", $currencySymbol)[0];
        $currencyId = $currency['id'];

        // Add user
        if ($uid != NULL && trim($uid) != '') {
            $dbUser->uid = $uid;
        }
        $dbUser->email = $email;
        $dbUser->password = password_hash($password, PASSWORD_DEFAULT);
        $dbUser->statusId = Constants::USER_STATUS_WAITING_VERIFICATION;
        $dbUser->fullname = $name;
        $dbUser->mobile = $mobile;
        $dbUser->roleId = $isDistributor ? Constants::USER_ROLE_DISTRIBUTOR_SYSTEM_ADMINISTRATOR : Constants::USER_ROLE_PHARMACY_SYSTEM_ADMINISTRATOR;
        $dbUser->language = "en";
        $dbUser->addReturnID();

        // Add entity
        $dbEntity = new GenericModel($this->db, "entity");
        $dbEntity->typeId = $isDistributor ? Constants::ENTITY_TYPE_DISTRIBUTOR : Constants::ENTITY_TYPE_PHARMACY;
        $dbEntity->name_ar = $entityName;
        $dbEntity->name_en = $entityName;
        $dbEntity->name_fr = $entityName;
        $dbEntity->countryId = $countryId;
        $dbEntity->currencyId = $currencyId;
        $dbEntity->addReturnID();

        // Add entity branch
        $dbEntityBranch = new GenericModel($this->db, "entityBranch");
        $dbEntityBranch->entityId = $dbEntity->id;
        $dbEntityBranch->name_ar = $entityName;
        $dbEntityBranch->name_en = $entityName;
        $dbEntityBranch->name_fr = $entityName;
        $dbEntityBranch->cityId = $cityId;
        $dbEntityBranch->address_ar = $address;
        $dbEntityBranch->address_en = $address;
        $dbEntityBranch->address_fr = $address;
        $dbEntityBranch->tradeLicenseNumber = $tradeLicenseNumber;
        $dbEntityBranch->tradeLicenseUrl = $pharmacyDocument;
        $dbEntityBranch->addReturnID();

        // Add account
        $dbAccount = new GenericModel($this->db, "account");
        $dbAccount->entityId = $dbEntity->id;
        $dbAccount->number = 100;
        $dbAccount->statusId = Constants::ACCOUNT_STATUS_ACTIVE;
        $dbAccount->addReturnID();

        // Add user account
        $dbUserAccount = new GenericModel($this->db, "userAccount");
        $dbUserAccount->userId = $dbUser->id;
        $dbUserAccount->accountId = $dbAccount->id;
        $dbUserAccount->statusId = Constants::ACCOUNT_STATUS_ACTIVE;
        $dbUserAccount->addReturnID();

        // Send verification email
        $allValues = new stdClass();
        $allValues->name = $name;
        $allValues->mobile = $mobile;
        $allValues->email = $email;
        $allValues->entityName = $entityName;
        $allValues->tradeLicenseNumber = $tradeLicenseNumber;
        $allValues->countryId = $countryId;
        $allValues->cityId = $cityId;
        $allValues->address = $address;
        $allValues->tradeLicenseUrl = $pharmacyDocument;
        $allValues->roleId = $dbUser->roleId;

        $dbCountry = new GenericModel($this->db, "country");
        $dbCountry->name = "name_en";
        $country = $dbCountry->getById($allValues->countryId)[0];
        $allValues->countryName = $country['name'];

        $dbCity = new GenericModel($this->db, "city");
        $dbCity->name = "nameEn";
        $city = $dbCity->getById($allValues->cityId)[0];
        $allValues->cityName = $city['name'];

        if (Helper::isPharmacy($dbUser->roleId)) {
            NotificationHelper::sendVerificationPharmacyNotification($this->f3, $this->db, $allValues, $dbUser->id, $dbEntity->id, $dbEntityBranch->id);
        } else {
            NotificationHelper::sendVerificationDistributorNotification($this->f3, $this->db, $allValues, $dbUser->id, $dbEntity->id, $dbEntityBranch->id);
        }

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_user')), $allValues);
    }

    function postSignUpDocumentUpload()
    {
        $allValidExtensions = [
            "pdf",
            "ppt",
            "docx",
            "jpeg",
            "jpg",
            "png",
        ];

        $success = false;

        $ext = pathinfo(basename($_FILES["file"]["name"]), PATHINFO_EXTENSION);
        if (in_array($ext, $allValidExtensions)) {
            $success = true;
        }

        if ($success) {
            $objResult = AumetFileUploader::upload("s3", $_FILES["file"], Helper::generateRandomString(64));
            $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_file')), ['url' => $objResult->fileLink]);
        }

        $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_unknownError', $this->f3->get('RESPONSE.entity_file')), null);
    }

    function postForgottenPassword()
    {
        if (!isset($this->requestData->email))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_email')), null);
        $email = $this->requestData->email;

        $dbUser = new GenericModel($this->db, "user");
        $dbUser->getByField("email", $email);

        if ($dbUser->dry()) {
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_doesntExist', $this->f3->get('RESPONSE.entity_email')), null);
        }
        $userResetToken = new GenericModel($this->db, "userResetToken");
        $userResetToken->getWhere('userId=' . $dbUser->id . " and userResetTokenStatusId=1");
        if (!$userResetToken->dry()) {
            $userResetToken->userResetTokenStatusId = 3;
            $userResetToken->update();
        }

        $userResetToken = new GenericModel($this->db, "userResetToken");
        $userResetToken->userId = $dbUser->id;
        $userResetToken->userResetTokenStatusId = 1;
        $userResetToken->token = Helper::generateRandomString(20);
        $userResetToken->createdAt = date('Y-m-d H:i:s');
        $userResetToken->updatedAt = date('Y-m-d H:i:s');
        $userResetToken->addReturnID();


        $payload = [
            'id' => $userResetToken->id,
            'userId' => $userResetToken->userId,
            'token' => $userResetToken->token,
        ];
        $jwt = new JWT(getenv('JWT_SECRET_KEY'), 'HS256', (86400 * 1), 10);
        $token = $jwt->encode($payload);

        NotificationHelper::resetPasswordNotification($this->f3, $this->db, $dbUser, $token);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.email_sent'));
    }

    function postResetPassword()
    {
        if (!isset($this->requestData->token))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_email')), null);
        $token = $this->requestData->token;

        if (!isset($this->requestData->password))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_email')), null);
        $password = $this->requestData->password;


        if (strlen($password) < 6) {
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.vMessage_passwordNotStrong'), null);
        }

        try {
            $jwt = new JWT(getenv('JWT_SECRET_KEY'), 'HS256', (86400 * 1), 10);
            $accessTokenPayload = $jwt->decode($token);
        } catch (\Exception $e) {
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_unknownError', $this->f3->get('RESPONSE.entity_token')) . ' 2', null);
        }
        if (!is_array($accessTokenPayload)) {
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_unknownError', $this->f3->get('RESPONSE.entity_token')) . ' 3', null);
        }

        $dbRequest = new GenericModel($this->db, "userResetToken");
        $dbRequest->getByField("id", $accessTokenPayload['id']);

        if ($dbRequest->dry()) {
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_unknownError', $this->f3->get('RESPONSE.entity_token')) . ' 4', null);
        }

        if ($dbRequest->userResetTokenStatusId != 1) {
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_unknownError', $this->f3->get('RESPONSE.entity_token')) . ' 5', null);
        }


        $dbUser = new GenericModel($this->db, "user");
        $dbUser->getByField("id", $dbRequest->userId);

        if ($dbUser->dry()) {
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_unknownError', $this->f3->get('RESPONSE.entity_token')) . ' 6', null);
        }

        $dbUser->password = password_hash($password, PASSWORD_DEFAULT);
        $dbRequest->updatedAt = date('Y-m-d H:i:s');

        if (!$dbUser->edit()) {
            $dbRequest->userResetTokenStatusId = 1;
            $dbRequest->edit();
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.403_unknownError', $this->f3->get('RESPONSE.entity_token')) . ' 7', null);
        }

        $dbRequest->userResetTokenStatusId = 3;
        $dbRequest->edit();

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.passwordChanged'));
    }

    public function postSignIn()
    {
        $idTokenString = null;
        if (isset($this->requestData->token)) {
            $idTokenString = $this->requestData->token;
        }

        $user = null;

        if ($idTokenString == null)
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_token')), null);

        $factory = (new Factory)->withServiceAccount($this->getRootDirectory() . '/config/aumet-com-firebase-adminsdk-2nsnx-64efaf5c39.json');

        $auth = $factory->createAuth();

        try {
            $verifiedIdToken = $auth->verifyIdToken($idTokenString);
            $uid = $verifiedIdToken->getClaim('sub');
            $user = $auth->getUser($uid);

            $dbUser = new GenericModel($this->db, "user");

            ////////////

            $dbUser->getWhere("uid = '$uid'");
            if ($dbUser->dry()) {
                $dbUser->getWhere("email = '$user->email'");
                if ($dbUser->dry()) {
                    $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_account')), $user);
                }
                $dbUser->uid = $uid;
                $dbUser->update();
            }

            if ($dbUser->statusId == Constants::USER_STATUS_WAITING_VERIFICATION) {
                $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.403_signInAccountNotActivated'), $user);
                return;
            } else if ($dbUser->statusId == Constants::USER_STATUS_PENDING_APPROVAL) {
                $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.403_signInAccountNotVerified'), $user);
                return;
            } else if ($dbUser->statusId !== Constants::USER_STATUS_ACCOUNT_ACTIVE) {
                $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_account')), $user);
            }

            ///////////


            $dbUser->getWhere("uid = '$uid' AND statusId = 3");
        } catch (\InvalidArgumentException $e) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $e->getMessage(), null);
        } catch (InvalidToken $e) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $e->getMessage(), null);
        }


        // if User doesn't exist
        if ($dbUser->dry()) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_account')), null);
        }

        if (!Helper::isPharmacy($dbUser->roleId)) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_pharmacyAccount')), null);
        }

        if (isset($this->deviceType)) {
            $deviceType = $this->deviceType;
        } else {
            $deviceType = 'undefined';
        }

        $payload = array(
            'userId' => $dbUser->id,
            'userEmail' => $dbUser->email,
            'fullName' => $dbUser->fullname
        );


        $jwt = new JWT(MainController::JWTSecretKey, 'HS256', (86400 * 30), 10);
        $jwtSignedKey = $jwt->encode($payload);

        $userSession = new GenericModel($this->db, 'userSession');

        $userSession->userId = $dbUser->id;
        $userSession->token = $jwtSignedKey;
        $userSession->deviceType = $deviceType;

        $userSession->add();

        $res = new stdClass();
        $res->id = $dbUser->id;
        $res->fullName = $dbUser->fullname;
        $res->email = $dbUser->email;
        $res->accessToken = $jwtSignedKey;

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_account')), $res);

        // $userCredential = new GenericModel($this->db, 'vw_customer_credentials');
        // $userCredential->load(array('email=? AND is_active=1', $this->requestData->email));

        // // if User doesn't exist
        // if ($userCredential->dry()) {
        //     $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_account')), null);
        // }

        // // If password is not valid
        // $passwordFound = false;
        // $passwordOld = false;
        // while (!$userCredential->dry()) {

        //     // passwords match
        //     if (password_verify($this->requestData->password, $userCredential->credential) || (md5($this->requestData->password) == $userCredential->credential)) {
        //         $passwordFound = true;

        //         // using an old password, continue loop incase the password is re-used
        //         // if not, then break
        //         if ($userCredential->credential_is_active == 0) {
        //             $passwordOld = true;
        //         } else {
        //             $passwordOld = false;
        //             break;
        //         }
        //     }
        //     $userCredential->next();
        // }

        // if (!$passwordFound) {
        //     $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.403_signInInvalidCredential'), null);
        // }

        // // If user is using an old credential
        // if ($passwordOld) {
        //     $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.403_signInOldCredential'), null);
        // }

        // $dbUser = new GenericModel($this->db, "customers");
        // $dbUser->getByField("id", $userCredential->customer_id);

        // NOTE: Add Customer validation here
        // if ($dbUser->stateId === Constants::USER_STATE_SIGNED_UP) {
        //     $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.403_signInAccountNotActivated'), null);
        // }

        // if ($dbUser->stateId === Constants::USER_STATE_VERIFIED) {
        //     $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.403_signInAccountNotReviewed'), null);
        // }
    }

    public function postSignInTest()
    {
        $dbUser = new GenericModel($this->db, 'user');

        // use secret hidden id to login
        if (getenv('ENV') != Constants::ENV_PROD) {
            $dbUser->load(array('id = ?', $this->requestData->id));
        }

        // if User doesn't exist
        if ($dbUser->dry()) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_account')), null);
        }

        if (!Helper::isPharmacy($dbUser->roleId)) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_pharmacyAccount')), null);
        }

        if (isset($this->deviceType)) {
            $deviceType = $this->deviceType;
        } else {
            $deviceType = 'undefined';
        }

        $payload = array(
            'userId' => $dbUser->id,
            'userEmail' => $dbUser->email,
            'fullName' => $dbUser->fullname
        );


        $jwt = new JWT(MainController::JWTSecretKey, 'HS256', (86400 * 30), 10);
        $jwtSignedKey = $jwt->encode($payload);

        $userSession = new GenericModel($this->db, 'userSession');

        $userSession->userId = $dbUser->id;
        $userSession->token = $jwtSignedKey;
        $userSession->deviceType = $deviceType;

        $userSession->add();

        $res = new stdClass();
        $res->id = $dbUser->id;
        $res->fullName = $dbUser->fullname;
        $res->email = $dbUser->email;
        $res->accessToken = $jwtSignedKey;

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_account')), $res);
    }


    function getRootDirectory()
    {
        return $this->f3->get('rootDIR');
    }

    public function getProfile()
    {
        $this->validateUser();

        $res = new UserProfile($this->objUser, $this->objEntityList, $this->accessToken);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_account')), $res);
    }

    public function postSignOut()
    {
        $this->validateUser();

        $userSession = new GenericModel($this->db, 'userSession');
        $userSession->load(array('userId=? and token = ?', $this->objUser->id, $this->accessToken));

        $userSession->isActive = 0;

        if (!$userSession->update()) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.403_queryError', $userSession->exception), null);
        }

        $this->accessToken = "";

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_signOut'), null);
    }

    function postUpdateUser()
    {
        $this->validateUser();

        if (!isset($this->requestData->name))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_name')), null);
        $name = $this->requestData->name;

        if (!isset($this->requestData->mobile))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_mobile')), null);
        $mobile = $this->requestData->mobile;

        $userId = $this->objUser->id;

        // Check if user exists
        $dbUser = new GenericModel($this->db, "user");
        $dbUser->getWhere("id=$userId");

        if ($dbUser->dry()) {
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_user')), null);
        }

        $dbUser->fullname = $name;
        $dbUser->mobile = $mobile;

        if (!$dbUser->update())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbUser->exception, null);


        $res = new UserProfile($this->objUser, $this->objEntityList, $this->accessToken);
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_updated', $this->f3->get('RESPONSE.entity_user')), $res);
    }
}
