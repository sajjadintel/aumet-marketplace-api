<?php

namespace App\Resources;

class StockStatusResource extends JsonResource
{
    public static function format($stockStatus)
    {
        $language = explode(',', \Base::instance()->get('LANGUAGE'))[0];
        $localeNameField = "name_{$language}";
        return [
            'id' => $stockStatus->id,
            'name' => $stockStatus->$localeNameField,
        ];
    }
}