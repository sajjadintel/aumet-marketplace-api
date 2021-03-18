<?php

namespace App\Resources;

use App\Models\Entity;

class EntityResource extends JsonResource
{
    public static function format($entity)
    {
        return [
            'id' => $entity->id,
            'name_en' => $entity->name_en,
            'name_ar' => $entity->name_ar,
            'name_fr' => $entity->name_fr,
            'country' => CountryResource::format($entity->countryId),
            'currency' => CurrencyResource::format($entity->currencyId),
            'status' => EntityStatusResource::format($entity->statusId),
            'image' => $entity->image,
            'created_at' => $entity->insertDateTime,
        ];
    }
}
