<?php

namespace App\Resources;

class DistributorResource extends JsonResource
{
    public static function format($distributor)
    {
        $language = explode(',', \Base::instance()->get('LANGUAGE'))[0];
        $localizedNameField = "name_{$language}";
        return [
            'id' => $distributor->id,
            'name' => $distributor->$localizedNameField,
            'country' => CountryResource::format($distributor->countryId),
            'currency' => CurrencyResource::format($distributor->currencyId),
            'image' => $distributor->image,
            'created_at' => $distributor->insertDateTime,
            'branches' => EntityBranchResource::collection($distributor->entityBranches),
        ];
    }
}