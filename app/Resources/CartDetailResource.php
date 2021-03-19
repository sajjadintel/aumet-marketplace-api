<?php

namespace App\Resources;

class CartDetailResource extends JsonResource
{
    public static function format($cartDetail)
    {
        return [
            'id' => $cartDetail->id,
            'entity_product' => EntityProductSellResource::format($cartDetail->entityProductId),
            'account' => AccountResource::format($cartDetail->accountId),
            'user' => $cartDetail->userId->id,
            'quantity' => $cartDetail->quantity,
            'quantity_free' => $cartDetail->quantityFree,
            'unitPrice' => $cartDetail->unitPrice,
            'vat' => $cartDetail->vat,
            'note' => $cartDetail->note,
            'created_at' => $cartDetail->insertDateTime,
            'updated_at' => $cartDetail->updateDateTime,
        ];
    }
} 