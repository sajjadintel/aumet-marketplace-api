<?php

use Ahc\Jwt\JWT;

class NotificationHelper {



    /**
     * sendVerificationPharmacyNotification
     *
     * @param \Base $f3 f3 instance
     * @param BaseModel $dbConnection db connection instance
     * @param stdClass $allValues all values
     * @param int $userId User id
     * @param int $entityId entity id
     * @param int $entityBranchId branch id
     */
    public static function sendVerificationPharmacyNotification($f3, $dbConnection, $allValues, $userId, $entityId, $entityBranchId)
    {
        $emailHandler = new EmailHandler($dbConnection);
        $emailFile = "email/layout.php";
        $f3->set('domainUrl', getenv('DOMAIN_URL'));
        $f3->set('title', 'Pharmacy Account Verification');
        $f3->set('emailType', 'pharmacyAccountVerification');


        $arrFields = [
            "Name" => $allValues->name,
            "Mobile" => $allValues->mobile,
            "Email" => $allValues->email,
            "Pharmacy Name" => $allValues->entityName,
            "Trade License Number" => $allValues->tradeLicenseNumber,
            "Country" => $allValues->countryName,
            "City" => $allValues->cityName,
            "Address" => $allValues->address,
        ];

        $f3->set('arrFields', $arrFields);

        $f3->set('tradeLicenseUrl', $allValues->tradeLicenseUrl);

        $payload = [
            'userId' => $userId,
            'entityId' => $entityId,
            'entityBranchId' => $entityBranchId
        ];
        $jwt = new JWT(getenv('JWT_SECRET_KEY'), 'HS256', (86400 * 30), 10);
        $token = $jwt->encode($payload);
        $f3->set('token', $token);

        $emailHandler->appendToAddress($allValues->email, $allValues->name);
        $htmlContent = View::instance()->render($emailFile);

        $subject = "Aumet - Pharmacy Account Verification";
        if (getenv('ENV') != Constants::ENV_PROD) {
            $subject .= " - (Test: " . getenv('ENV') . ")";

            if (getenv('ENV') == Constants::ENV_LOC) {
                $emailHandler->appendToAddress("carl8smith94@gmail.com", "Antoine Abou Cherfane");
                $emailHandler->appendToAddress("patrick.younes.1.py@gmail.com", "Patrick");
                $emailHandler->appendToAddress("n.javaid@aumet.com", "Naveed Javaid");
                $emailHandler->appendToAddress("sajjadintel@gmail.com", "Sajad Abbasi");
            }
        }

        $emailHandler->sendEmail(Constants::EMAIL_PHARMACY_ACCOUNT_VERIFICATION, $subject, $htmlContent);
    }

    /**
     * sendVerificationDistributorNotification
     *
     * @param \Base $f3 f3 instance
     * @param BaseModel $dbConnection db connection instance
     * @param stdClass $allValues all values
     * @param int $userId User id
     * @param int $entityId entity id
     * @param int $entityBranchId branch id
     */
    public static function sendVerificationDistributorNotification($f3, $dbConnection, $allValues, $userId, $entityId, $entityBranchId)
    {
        $emailHandler = new EmailHandler($dbConnection);
        $emailFile = "email/layout.php";
        $f3->set('domainUrl', getenv('DOMAIN_URL'));
        $f3->set('title', 'Distributor Account Verification');
        $f3->set('emailType', 'distributorAccountVerification');


        $dbCity = new BaseModel($dbConnection, "city");
        $dbCity->name = "nameEn";
        $city = $dbCity->getById($allValues->cityId)[0];
        $cityName = $city['name'];

        $arrFields = [
            "Name" => $allValues->name,
            "Mobile" => $allValues->mobile,
            "Email" => $allValues->email,
            "Distributor Name" => $allValues->entityName,
            "Trade License Number" => $allValues->tradeLicenseNumber,
            "Country" => $allValues->countryName,
            "City" => $allValues->cityName,
            "Address" => $allValues->address,
        ];

        $f3->set('arrFields', $arrFields);

        $f3->set('tradeLicenseUrl', $allValues->tradeLicenseUrl);

        $payload = [
            'userId' => $userId,
            'entityId' => $entityId,
            'entityBranchId' => $entityBranchId
        ];
        $jwt = new JWT(getenv('JWT_SECRET_KEY'), 'HS256', (86400 * 30), 10);
        $token = $jwt->encode($payload);
        $f3->set('token', $token);

        $emailHandler->appendToAddress($allValues->email, $allValues->name);
        $htmlContent = View::instance()->render($emailFile);

        $subject = "Aumet - Distributor Account Verification";
        if (getenv('ENV') != Constants::ENV_PROD) {
            $subject .= " - (Test: " . getenv('ENV') . ")";

            if (getenv('ENV') == Constants::ENV_LOC) {
                $emailHandler->appendToAddress("carl8smith94@gmail.com", "Antoine Abou Cherfane");
                $emailHandler->appendToAddress("patrick.younes.1.py@gmail.com", "Patrick");
                $emailHandler->appendToAddress("n.javaid@aumet.com", "Naveed Javaid");
                $emailHandler->appendToAddress("sajjadintel@gmail.com", "Sajad Abbasi");
            }
        }

        $emailHandler->sendEmail(Constants::EMAIL_DISTRIBUTOR_ACCOUNT_VERIFICATION, $subject, $htmlContent);
    }


}
