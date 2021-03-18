<?php

namespace App\Resources;

class CountryResource extends JsonResource
{
    public static function format($country)
    {
        return [
            'id' => $country->id,
            'name_en' => $country->name_en,
            'name_ar' => $country->name_ar,
            'name_fr' => $country->name_fr,
            'currency' => $country->currency,
            'is_registered_from' => $country->isRegisteredFrom,
            'code' => $country->code,
        ];
    }
}
