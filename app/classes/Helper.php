<?php

class Helper
{

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
}
