<?php

namespace App\Resources;

class CityResource extends JsonResource
{
    public static function format($city)
    {
        $localizedNameField = 'name' . ucfirst(\Base::instance()->get('locale'));
        return [
            'id' => $city->id,
            'name' => $city->$localizedNameField
        ];
    }
}
