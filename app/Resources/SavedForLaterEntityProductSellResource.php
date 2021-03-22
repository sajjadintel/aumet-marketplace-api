<?php

namespace App\Resources;

class SavedForLaterEntityProductSellResource extends JsonResource
{
    public static function format($entityProduct)
    {
        $language = explode(',', \Base::instance()->get('LANGUAGE'))[0];
        $localeNameField = "name_{$language}";
        return [
            'product_id' => $entityProduct->productId->id,
            'product_name' => $entityProduct->productId->$localeNameField,
            'entity_product_id' => $entityProduct->id,
            'price' => $entityProduct->unitPrice,
            'stock' => $entityProduct->stock,
            'expiry_date' => $entityProduct->expiryDate,
            'image' => $entityProduct->image,
            'stock_status' => StockStatusResource::format($entityProduct->stockStatusId),
            'bonus' => EntityProductSellBonusDetailResource::collection($entityProduct->bonusDetails),
        ];
    }
}