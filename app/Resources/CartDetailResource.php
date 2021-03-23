<?php

namespace App\Resources;

class CartDetailResource extends JsonResource
{
    public static function format($cartDetail)
    {
        return [
            'id' => $cartDetail->id,
            'accountId' => $cartDetail->accountId->id,
            'userId' => $cartDetail->userId->id,
            'quantity' => $cartDetail->quantity,
            'quantity_free' => $cartDetail->quantityFree,
            'unitPrice' => $cartDetail->unitPrice,
            'vat' => $cartDetail->vat,
            'note' => $cartDetail->note,
            'created_at' => $cartDetail->insertDateTime,
            'updated_at' => $cartDetail->updateDateTime,
            'product' => EntityProductSellViewResource::format($cartDetail->entityProductId),
        ];
    }
} 