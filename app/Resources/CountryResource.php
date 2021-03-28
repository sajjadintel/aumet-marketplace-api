<?php

namespace App\Resources;

class CountryResource extends JsonResource
{
    public static function format($country)
    {
        $language = explode(',', \Base::instance()->get('LANGUAGE'))[0];
        $localizedNameField = "name_{$language}";
        return [
            'id' => $country->id,
            'name' => $country->$localizedNameField,
            'currency' => $country->currency,
            'isRegisteredFrom' => $country->isRegisteredFrom,
            'code' => $country->code,
        ];
    }
}
