<?php

namespace App\Resources;

class EntityProductSellBonusDetailResource extends JsonResource
{
    public static function format($item)
    {
        $localeNameField = 'name_' . strtolower(\Base::instance()->get('LANGUAGE'));
        return [
            'id' => $item->id,
            'bonus_type' => $item->bonusTypeId->$localeNameField,
            'min_order' => $item->minOrder,
            'bonus' => $item->bonus,
        ];
    }
}