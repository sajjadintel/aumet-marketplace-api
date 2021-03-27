<?php
class UserProfile
{
    public $id, $fullName, $email, $roleName, $cartCount, $entityList, $accessToken;

    function __construct($objUser, $objEntityList, $accessToken)
    {
        $this->id = $objUser->id;
        $this->fullName = $objUser->fullname;
        $this->email = $objUser->email;
        $this->mobile = $objUser->mobile;
        $this->roleName = $objUser['roleName'];
        $this->cartCount = $objUser->cartCount;
        $this->entityList = $objEntityList;
        $this->accessToken = $accessToken;
    }
}
