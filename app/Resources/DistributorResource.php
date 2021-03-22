<?php

namespace App\Resources;

class DistributorResource extends JsonResource
{
    public static function format($distributor)
    {
        $localizedNameField = 'name_' . \Base::instance()->get('locale');
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