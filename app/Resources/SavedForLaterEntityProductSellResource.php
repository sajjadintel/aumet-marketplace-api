<?php

namespace App\Resources;

class SavedForLaterEntityProductSellResource extends JsonResource
{
    public static function format($entityProduct)
    {
        $localeNameField = 'name_' . strtolower(\Base::instance()->get('LANGUAGE'));
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