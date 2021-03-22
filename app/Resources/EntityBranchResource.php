<?php

namespace App\Resources;

class EntityBranchResource extends JsonResource
{
    public static function format($entityBranch)
    {
        $localizedNameField = 'name_' . \Base::instance()->get('locale');
        $localizedAddressField = 'address_' . \Base::instance()->get('locale');
        return [
            'id' => $entityBranch->id,
            'name' => $entityBranch->$localizedNameField,
            'image' => $entityBranch->image,
            'created_at' => $entityBranch->insertDateTime,
            'address' => $entityBranch->$localizedAddressField,
            'city' => CityResource::format($entityBranch->cityId),
        ];
    }
}
