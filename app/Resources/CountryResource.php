<?php

namespace App\Resources;

class CountryResource extends JsonResource
{
    public static function format($country)
    {
        $localizedNameField = 'name_' . \Base::instance()->get('LANGUAGE');
        return [
            'id' => $country->id,
            'name' => $country->$localizedNameField,
            'currency' => $country->currency,
            'is_registered_from' => $country->isRegisteredFrom,
            'code' => $country->code,
        ];
    }
}
