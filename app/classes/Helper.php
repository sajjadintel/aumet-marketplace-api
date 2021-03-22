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
    }

}
