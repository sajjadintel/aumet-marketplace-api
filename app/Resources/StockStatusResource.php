<?php

namespace App\Resources;

class StockStatusResource extends JsonResource
{
    public static function format($stockStatus)
    {
        $localeNameField = 'name_' . strtolower(\Base::instance()->get('locale'));
        return [
            'id' => $stockStatus->id,
            'name' => $stockStatus->$localeNameField,
        ];
    }
}