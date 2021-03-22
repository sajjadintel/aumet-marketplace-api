<?php

use Ahc\Jwt\JWT;

class ProfileController extends MainController {

    function postPharmacyProfile()
    {
        if (!isset($this->requestData->entityName))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_entityName')), null);
        $entityName = $this->requestData->entityName;

        if (!isset($this->requestData->tradeLicenseNumber))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_tradeLicenseNumber')), null);
        $tradeLicenseNumber = $this->requestData->tradeLicenseNumber;

        if (!isset($this->requestData->address))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_address')), null);
        $address = $this->requestData->address;

        if (!isset($this->requestData->entityDocument))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_entityDocument')), null);
        $entityDocument = $this->requestData->entityDocument;


        $this->checkLength($entityName, 'pharmacyName', 100, 4);
        $this->checkLength($address, 'address', 500, 4);
        $this->checkLength($entityDocument, 'entityDocument', 1000, 4);
        if (strlen($tradeLicenseNumber) > 0) {
            $this->checkLength($tradeLicenseNumber, 'tradeLicenseNumber', 200, 4);
        }

        $userId = $this->objUser->id;

        // Check if user exists
        $dbUser = new GenericModel($this->db, "vwEntityUserProfile");
        $dbUser->getWhere("userId=$userId");

        if ($dbUser->dry()) {
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_user')), null);
        }

        $dbEntity = new GenericModel($this->db, "entity");
        $dbEntity->getByField("id", $dbUser->entityId);

        $entityBranchId = $dbUser->entityBranchId;
        $dbEntityBranch = new GenericModel($this->db, "entityBranch");

        if (strlen($tradeLicenseNumber) > 0) {
            $dbEntityBranch->getWhere("id != $entityBranchId AND tradeLicenseNumber='$tradeLicenseNumber'");

            if (!$dbEntityBranch->dry()) {
                $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_tradeLicenseNumber')), null);
            }
        }

        $dbEntityBranch->getByField("id", $entityBranchId);
        $dbEntityBranch->address_ar = $address;
        $dbEntityBranch->address_en = $address;
        $dbEntityBranch->address_fr = $address;

        if (
            $dbEntity->name_en != $entityName
            || $dbEntityBranch->tradeLicenseNumber != $tradeLicenseNumber
            || $dbEntityBranch->tradeLicenseUrl != $entityDocument
        ) {

            $mapFieldNameOldNewValue = [];
            if ($dbEntity->name_en != $entityName) {
                $mapFieldNameOldNewValue["entity.name_ar"] = [$dbEntity->name_ar, $entityName];
                $mapFieldNameOldNewValue["entity.name_en"] = [$dbEntity->name_en, $entityName];
                $mapFieldNameOldNewValue["entity.name_fr"] = [$dbEntity->name_fr, $entityName];
                $mapFieldNameOldNewValue["entityBranch.name_ar"] = [$dbEntity->name_ar, $entityName];
                $mapFieldNameOldNewValue["entityBranch.name_en"] = [$dbEntity->name_en, $entityName];
                $mapFieldNameOldNewValue["entityBranch.name_fr"] = [$dbEntity->name_fr, $entityName];
            }

            if ($dbEntityBranch->tradeLicenseNumber != $tradeLicenseNumber) {
                $mapFieldNameOldNewValue["entityBranch.tradeLicenseNumber"] = [$dbEntityBranch->tradeLicenseNumber, $tradeLicenseNumber];
            }

            if ($dbEntityBranch->tradeLicenseUrl != $entityDocument) {
                $mapFieldNameOldNewValue["entityBranch.tradeLicenseUrl"] = [$dbEntityBranch->tradeLicenseUrl, $entityDocument];
            }

            $dbEntityChangeApproval = new GenericModel($this->db, "entityChangeApproval");
            $dbEntityChangeApproval->tradeLicenseUrl = $entityDocument;
            $dbEntityChangeApproval->entityId = $dbUser->entityId;
            $dbEntityChangeApproval->userId = $userId;
            $dbEntityChangeApproval->addReturnID();

            $dbEntityChangeApprovalField = new GenericModel($this->db, "entityChangeApprovalField");
            $mapDisplayNameOldNewValue = [];
            foreach ($mapFieldNameOldNewValue as $fieldName => $oldNewValue) {
                $oldValue = $oldNewValue[0];
                $newValue = $oldNewValue[1];

                // Add row in entityChangeApprovalField
                $dbEntityChangeApprovalField->entityChangeApprovalId = $dbEntityChangeApproval->id;
                $dbEntityChangeApprovalField->fieldName = $fieldName;
                $dbEntityChangeApprovalField->oldValue = $oldValue;
                $dbEntityChangeApprovalField->newValue = $newValue;
                $dbEntityChangeApprovalField->add();

                // Fill map used to display data in the mail approval
                $allParts = explode(".", $fieldName);
                $name = end($allParts);
                if ($name == "name_en") {
                    $displayName = "Pharmacy Name";
                } else if ($name == "tradeLicenseNumber") {
                    $displayName = "Trade License Number";
                } else {
                    $displayName = "";
                }

                if ($displayName) {
                    $mapDisplayNameOldNewValue[$displayName] = $oldNewValue;
                }
            }
            $message = $this->f3->get("RESPONSE.vModule_profile_requestSent");

            $approvalUrl = "web/review/pharmacy/profile/approve";
            $this->sendChangeApprovalEmail($dbEntityChangeApproval->id, $mapDisplayNameOldNewValue, $dbEntityBranch->tradeLicenseUrl, $entityDocument, $approvalUrl, $dbUser->userEmail);
        } else {
            $message = $this->f3->get("RESPONSE.vModule_profile_myProfileSaved");
        }

        $dbEntityBranch->update();

        $this->sendSuccess(Constants::HTTP_OK, $message);
    }

    function sendChangeApprovalEmail($entityChangeApprovalId, $mapDisplayNameOldNewValue, $oldTradeLicenseUrl, $tradeLicenseUrl, $approvalUrl, $userEmail)
    {
        $emailHandler = new EmailHandler($this->db);
        $emailFile = "emails/layout.php";
        $this->f3->set('domainUrl', getenv('DOMAIN_URL'));
        $this->f3->set('title', 'Change Profile Approval');
        $this->f3->set('emailType', 'changeProfileApproval');

        $this->f3->set('mapDisplayNameOldNewValue', $mapDisplayNameOldNewValue);
        if ($oldTradeLicenseUrl != $tradeLicenseUrl) {
            $this->f3->set('oldTradeLicenseUrl', $oldTradeLicenseUrl);
        }
        $this->f3->set('tradeLicenseUrl', $tradeLicenseUrl);
        $this->f3->set('approvalUrl', $approvalUrl);
        $this->f3->set('userEmail', $userEmail);

        $payload = [
            'entityChangeApprovalId' => $entityChangeApprovalId
        ];
        $jwt = new JWT(getenv('JWT_SECRET_KEY'), 'HS256', (86400 * 30), 10);
        $token = $jwt->encode($payload);
        $this->f3->set('token', $token);

        $emailList = explode(';', getenv('ADMIN_SUPPORT_EMAIL'));
        for ($i = 0; $i < count($emailList); $i++) {
            if (!$emailList[$i]) {
                continue;
            }

            $currentEmail = explode(',', $emailList[$i]);
            if (count($currentEmail) == 2) {
                $emailHandler->appendToAddress($currentEmail[0], $currentEmail[1]);
            } else {
                $emailHandler->appendToAddress($currentEmail[0], $currentEmail[0]);
            }
        }

        $htmlContent = View::instance()->render($emailFile);

        $subject = "Aumet - Change Profile Approval";
        if (getenv('ENV') != Constants::ENV_PROD) {
            $subject .= " - (Test: " . getenv('ENV') . ")";

            if (getenv('ENV') == Constants::ENV_LOCAL) {
                $emailHandler->appendToAddress("carl8smith94@gmail.com", "Antoine Abou Cherfane");
                $emailHandler->appendToAddress("sajjadintel@gmail.com", "Sajad Abbasi");
                $emailHandler->appendToAddress("patrick.younes.1.py@gmail.com", "Patrick");
            }
        }
        $emailHandler->sendEmail(Constants::EMAIL_CHANGE_PROFILE_APPROVAL, $subject, $htmlContent);
    }

}
