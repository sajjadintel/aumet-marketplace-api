<?php

namespace App\Resources;

class ProductResource extends JsonResource
{
    public static function format($product)
    {
        return [
            'id' => $product->id,
            'name_en' => $product->name_en,
            'name_ar' => $product->name_ar,
            'name_fr' => $product->name_fr,
            'subtitle_en' => $product->subtitle_en,
            'subtitle_ar' => $product->subtitle_ar,
            'subtitle_fr' => $product->subtitle_fr,
            'description_en' => $product->description_en,
            'description_ar' => $product->description_ar,
            'description_fr' => $product->description_fr,
            'country' => CountryResource::format($product->madeInCountryId),
            'image' => $product->image,
            'expirt_date' => $product->expiryDate,
            'created_at' => $product->insertDateTime,
        ];
    }
}