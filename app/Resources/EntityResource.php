<?php

namespace App\Resources;

use App\Models\Entity;

class EntityResource extends JsonResource
{
    public static function format($entity)
    {
        $language = explode(',', \Base::instance()->get('LANGUAGE'))[0];
        $localizedNameField = "name_{$language}";
        return [
            'id' => $entity->id,
            'name' => $entity->$localizedNameField,
            'country' => CountryResource::format($entity->countryId),
            'currency' => CurrencyResource::format($entity->currencyId),
            'status' => EntityStatusResource::format($entity->statusId),
            'image' => $entity->image,
            'created_at' => $entity->insertDateTime,
        ];
    }
}
