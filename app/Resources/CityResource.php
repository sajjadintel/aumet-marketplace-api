<?php

namespace App\Resources;

class CityResource extends JsonResource
{
    public static function format($city)
    {
        $language = explode(',', \Base::instance()->get('LANGUAGE'))[0];
        $localizedNameField = 'name' . ucfirst($language);
        return [
            'id' => $city->id,
            'name' => $city->$localizedNameField
        ];
    }
}
