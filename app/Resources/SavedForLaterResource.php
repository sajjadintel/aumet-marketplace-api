<?php

namespace App\Resources;

class SavedForLaterResource extends JsonResource
{
    public static function format($savedForLater)
    {
        return [
            'id' => $savedForLater->id,
            'quantity' => $savedForLater->quantity,
            'accountId' => $savedForLater->accountId->id,
            'createdAt' => $savedForLater->createdAt,
            'product' => EntityProductSellViewResource::format($savedForLater->entityProductId),
        ];
    }
}
