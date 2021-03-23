<?php

namespace App\Resources;

use ProductHelper;

class EntityProductSellViewResource extends JsonResource
{
    public static function format($product)
    {
        $language = explode(',', \Base::instance()->get('LANGUAGE'))[0];
        $productName = "productName_{$language}";
        $entityName = "entityName_{$language}";
        $bonusTypeName = "bonusTypeName_{$language}";
        $madeInCountryName = "madeInCountryName_{$language}";
        $availableQuantity = ProductHelper::getAvailableQuantity($product->stock, $product->maximumOrderQuantity);
        $bonusInfo = ProductHelper::getBonusInfo(
            \Base::instance()->get('dbConnectionMain'),
            $language,
            \Base::instance()->get('objEntityList'),
            $product->id,
            $product->entityId,
            $availableQuantity
        );
        $product->bonuses = $bonusInfo->arrBonus;

        return [
                'id' => $product->id,
                'entityId' => $product->entityId,
                'entityName_ar' => $product->entityName_ar,
                'entityName_en' => $product->entityName_en,
                'entityName_fr' => $product->entityName_fr,
                'entityImage' => $product->entityImage,
                'productId' => $product->productId,
                'totalOrderQuantity' => $product->totalOrderQuantity,
                'totalOrderCount' => $product->totalOrderCount,
                'maximumOrderQuantity' => $product->maximumOrderQuantity,
                'minimumOrderQuantity' => $product->minimumOrderQuantity,
                'scientificNameId' => $product->scientificNameId,
                'scientificName' => $product->scientificName,
                'insertDateTime' => $product->insertDateTime,
                'productName_ar' => $product->productName_ar,
                'productName_en' => $product->productName_en,
                'productName_fr' => $product->productName_fr,
                'subtitle_ar' => $product->subtitle_ar,
                'subtitle_en' => $product->subtitle_en,
                'subtitle_fr' => $product->subtitle_fr,
                'description_ar' => $product->description_ar,
                'description_en' => $product->description_en,
                'description_fr' => $product->description_fr,
                'strength' => $product->strength,
                'manufacturerName' => $product->manufacturerName,
                'batchNumber' => $product->batchNumber,
                'itemCode' => $product->itemCode,
                'productExpiryDate' => $product->productExpiryDate,
                'productCategoryId' => $product->productCategoryId,
                'productCategoryName_en' => $product->productCategoryName_en,
                'productCategoryName_ar' => $product->productCategoryName_ar,
                'productCategoryName_fr' => $product->productCategoryName_fr,
                'productSubcategoryId' => $product->productSubcategoryId,
                'productSubcategoryName_en' => $product->productSubcategoryName_en,
                'productSubcategoryName_ar' => $product->productSubcategoryName_ar,
                'productSubcategoryName_fr' => $product->productSubcategoryName_fr,
                'categoryId' => $product->categoryId,
                'subCategoryId' => $product->subCategoryId,
                'category_name_en' => $product->category_name_en,
                'category_name_ar' => $product->category_name_ar,
                'category_name_fr' => $product->category_name_fr,
                'sub_category_name_en' => $product->sub_category_name_en,
                'sub_category_name_ar' => $product->sub_category_name_ar,
                'sub_category_name_fr' => $product->sub_category_name_fr,
                'unitPrice' => $product->unitPrice,
                'vat' => $product->vat,
                'currencyId' => $product->currencyId,
                'currency' => $product->currency,
                'expiryDate' => $product->expiryDate,
                'statusId' => $product->statusId,
                'stockStatusId' => $product->stockStatusId,
                'stockStatusName_ar' => $product->stockStatusName_ar,
                'stockStatusName_en' => $product->stockStatusName_en,
                'stockStatusName_fr' => $product->stockStatusName_fr,
                'stock' => $product->stock,
                'stockUpdateDateTime' => $product->stockUpdateDateTime,
                'image' => $product->image,
                'imageAlt' => $product->imageAlt,
                'madeInCountryId' => $product->madeInCountryId,
                'madeInCountryName_ar' => $product->madeInCountryName_ar,
                'madeInCountryName_en' => $product->madeInCountryName_en,
                'madeInCountryName_fr' => $product->madeInCountryName_fr,
                'bonusCustomerGroupId' => $product->bonusCustomerGroupId,
                'bonusCustomerGroupName_ar' => $product->bonusCustomerGroupName_ar,
                'bonusCustomerGroupName_en' => $product->bonusCustomerGroupName_en,
                'bonusCustomerGroupName_fr' => $product->bonusCustomerGroupName_fr,
                'bonusTypeId' => $product->bonusTypeId,
                'bonusFormula' => $product->bonusFormula,
                'bonusTypeName_ar' => $product->bonusTypeName_ar,
                'bonusTypeName_en' => $product->bonusTypeName_en,
                'bonusTypeName_fr' => $product->bonusTypeName_fr,
                'defaultQuantity' => $product->defaultQuantity,
                'bonusConfig' => $product->bonusConfig,
                'quantityOrdered' => $product->quantityOrdered,
                'productName' => $product->$productName,
                'entityName' => $product->$entityName,
                'bonusTypeName' => $product->$bonusTypeName,
                'madeInCountryName' => $product->$madeInCountryName,
                'bonuses' => $bonusInfo->arrBonus
        ];
    }
}