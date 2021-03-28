<?php

namespace App\Resources;

class EntityBranchResource extends JsonResource
{
    public static function format($entityBranch)
    {
        $language = explode(',', \Base::instance()->get('LANGUAGE'))[0];
        $localizedNameField = "name_{$language}";
        $localizedAddressField = "address_{$language}";
        return [
            'id' => $entityBranch->id,
            'name' => $entityBranch->$localizedNameField,
            'image' => $entityBranch->image,
            'created_at' => $entityBranch->insertDateTime,
            'address' => $entityBranch->$localizedAddressField,
            'trade_license_number' => $entityBranch->tradeLicenseNumber,
            'trade_license_url' => $entityBranch->tradeLicenseUrl,
            'city' => CityResource::format($entityBranch->cityId),
        ];
    }
}
