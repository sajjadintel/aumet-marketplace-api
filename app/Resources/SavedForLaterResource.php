<?php

namespace App\Resources;

class SavedForLaterResource extends JsonResource
{
    public static function format($savedForLater)
    {
        return [
            'id' => $savedForLater->id,
            'entity_product_id' => $savedForLater->entityProductId->id,
            'account_id' => $savedForLater->accountId->id,
            'quantity' => $savedForLater->quantity,
            'created_at' => $savedForLater->createdAt,
        ];
    }
}
