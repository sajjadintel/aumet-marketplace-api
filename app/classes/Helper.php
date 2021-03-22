<?php

class Helper {

    public static function idListFromArray($array)
    {

        $ids = '';
        foreach ($array as $key => $value) {
            if ($ids != '') {
                $ids .= ', ';
            }
            $ids .= $key;
        }
        return $ids;
    }


    public static function addEditableOrders($orders)
    {
        for ($i = 0; $i < count($orders); $i++) {
            $orders[$i]['isEditable'] = $orders[$i]['statusId'] == 1 ? 1 : 0;
        }
        return $orders;
    }

    public static function addCancellableOrders($orders)
    {
        for ($i = 0; $i < count($orders); $i++) {
            $orders[$i]['isCancellable'] = $orders[$i]['statusId'] == 1 ? 1 : 0;
        }
        return $orders;
    }

    public static function addColorPalette($orders)
    {
        $mapPaletteToOrderStatus = [
            Constants::ORDER_STATUS_PENDING => 'main',
            Constants::ORDER_STATUS_ONHOLD => 'warning',
            Constants::ORDER_STATUS_PROCESSING => 'info',
            Constants::ORDER_STATUS_COMPLETED => 'success',
            Constants::ORDER_STATUS_CANCELED => 'danger',
            Constants::ORDER_STATUS_RECEIVED => 'success',
            Constants::ORDER_STATUS_PAID => 'success',
            Constants::ORDER_STATUS_MISSING_PRODUCTS => 'danger',
            Constants::ORDER_STATUS_CANCELED_PHARMACY => 'danger',
        ];

        for ($i = 0; $i < count($orders); $i++) {
            $orders[$i]['colorPalette'] = $mapPaletteToOrderStatus[$orders[$i]['statusId']] ?? 'main';
        }
        return $orders;

    public static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    public static function isDistributor($roleId)
    {
        if ($roleId == 10 || $roleId == 20 || $roleId == 30) {
            return true;
        }
        return false;
    }

    public static function isPharmacy($roleId)
    {
        if ($roleId == 40 || $roleId == 41) {
            return true;
        }
        return false;
    }

}
