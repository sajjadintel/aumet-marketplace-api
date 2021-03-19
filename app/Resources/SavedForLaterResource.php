<?php

namespace App\Resources;

class SavedForLaterResource extends JsonResource
{
    public static function format($savedForLater)
    {
        return [
            'id' => $savedForLater->id,
            'quantity' => $savedForLater->quantity,
            'account_id' => $savedForLater->accountId->id,
            'created_at' => $savedForLater->createdAt,
            'product' => SavedForLaterEntityProductSellResource::format($savedForLater->entityProductId),
        ];
    }
}
