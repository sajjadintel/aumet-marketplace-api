<?php

class ProductHelper {

    public static function getAvailableQuantity($stock, $maximumOrderQuantity)
    {
        $availableQuantity = min($stock, $maximumOrderQuantity);
        if (!$maximumOrderQuantity)
            $availableQuantity = $stock;
        if (!$stock)
            $availableQuantity = 0;

        return $availableQuantity;
    }

    public static function getBonusInfo($dbConnection, $language, $objEntityList, $entityProductId, $entityId, $availableQuantity, $quantity = 0)
    {
        // Get all related bonuses
        $mapBonusIdRelationGroup = [];
        $mapSellerIdRelationGroupId = [];
        $dbBonus = new GenericModel($dbConnection, "vwEntityProductSellBonusDetail");
        $dbBonus->bonusTypeName = "bonusTypeName_" . $language;
        $arrBonus = $dbBonus->getWhere("entityProductId = $entityProductId AND isActive = 1");
        $arrBonusId = [];
        foreach ($arrBonus as $bonus) {
            array_push($arrBonusId, $bonus['id']);
        }

        // Get special bonuses
        if (count($arrBonusId) > 0) {
            $dbBonusRelationGroup = new GenericModel($dbConnection, "entityProductSellBonusDetailRelationGroup");
            $arrBonusRelationGroup = $dbBonusRelationGroup->getWhere("bonusId IN (" . implode(",", $arrBonusId) . ")");

            foreach ($arrBonusRelationGroup as $bonusRelationGroup) {
                $bonusId = $bonusRelationGroup['bonusId'];
                $arrRelationGroup = [];
                if (array_key_exists($bonusId, $mapBonusIdRelationGroup)) {
                    $arrRelationGroup = $mapBonusIdRelationGroup[$bonusId];
                }

                array_push($arrRelationGroup, $bonusRelationGroup['relationGroupId']);
                $mapBonusIdRelationGroup[$bonusId] = $arrRelationGroup;
            }
        }

        $arrEntityId = Helper::idListFromArray($objEntityList);
        $dbEntityRelation = new GenericModel($dbConnection, "entityRelation");
        $arrEntityRelation = $dbEntityRelation->getWhere("entityBuyerId IN ($arrEntityId)");
        foreach ($arrEntityRelation as $entityRelation) {
            $mapSellerIdRelationGroupId[$entityRelation['entitySellerId']] = $entityRelation['relationGroupId'];
        }

        $arrProductBonus = [];
        $activeBonus = new stdClass();
        $activeBonus->totalBonus = 0;
        foreach ($arrBonus as $bonus) {
            $bonusId = $bonus['id'];

            // Check if bonus available for buyer
            $valid = false;
            if (array_key_exists($bonusId, $mapBonusIdRelationGroup)) {
                $arrRelationGroup = $mapBonusIdRelationGroup[$bonusId];
                if (array_key_exists($entityId, $mapSellerIdRelationGroupId)) {
                    $relationGroupId = $mapSellerIdRelationGroupId[$entityId];
                    if (in_array($relationGroupId, $arrRelationGroup)) {
                        $valid = true;
                    }
                }
            } else {
                $valid = true;
            }

            if (!$valid) {
                continue;
            }

            $bonusType = $bonus['bonusTypeName'];
            $bonusTypeId = $bonus['bonusTypeId'];
            $bonusMinOrder = $bonus['minOrder'];
            $bonusBonus = $bonus['bonus'];

            // Check if bonus is possible
            $totalOrder = 0;
            if ($bonusTypeId == Constants::BONUS_TYPE_FIXED || $bonusTypeId == Constants::BONUS_TYPE_DYNAMIC) {
                $totalOrder = $bonusMinOrder + $bonusBonus;
            } else if ($bonusTypeId == Constants::BONUS_TYPE_PERCENTAGE) {
                $totalOrder = $bonusMinOrder + floor($bonusBonus * $bonusMinOrder / 100);
            }
            if ($totalOrder > $availableQuantity) {
                continue;
            }

            $totalBonus = 0;
            if ($quantity >= $bonusMinOrder) {
                if ($bonusTypeId == Constants::BONUS_TYPE_FIXED) {
                    $totalBonus = $bonusBonus;
                } else if ($bonusTypeId == Constants::BONUS_TYPE_DYNAMIC) {
                    $totalBonus = floor($quantity / $bonusMinOrder) * $bonusBonus;
                } else if ($bonusTypeId == Constants::BONUS_TYPE_PERCENTAGE) {
                    $totalBonus = floor($quantity * $bonusBonus / 100);
                }
            }

            if ($bonusTypeId == Constants::BONUS_TYPE_PERCENTAGE) {
                $bonusBonus .= "%";
            }

            if ($totalBonus > $activeBonus->totalBonus) {
                $activeBonus->bonusType = $bonusType;
                $activeBonus->minQty = $bonusMinOrder;
                $activeBonus->bonuses = $bonusBonus;
                $activeBonus->totalBonus = $totalBonus;
            }

            $found = false;
            for ($j = 0; $j < count($arrProductBonus); $j++) {
                $productBonus = $arrProductBonus[$j];
                if ($productBonus->bonusType == $bonusType) {
                    $arrMinQty = $productBonus->arrMinQty;
                    array_push($arrMinQty, $bonusMinOrder);
                    $productBonus->arrMinQty = $arrMinQty;

                    $arrBonuses = $productBonus->arrBonuses;
                    array_push($arrBonuses, $bonusBonus);
                    $productBonus->arrBonuses = $arrBonuses;

                    $arrProductBonus[$j] = $productBonus;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $productBonus = new stdClass();
                $productBonus->bonusType = $bonusType;
                $productBonus->arrMinQty = [$bonusMinOrder];
                $productBonus->arrBonuses = [$bonusBonus];
                array_push($arrProductBonus, $productBonus);
            }
        }

        $bonusInfo = new stdClass();
        $bonusInfo->arrBonus = $arrProductBonus;
        $bonusInfo->activeBonus = $activeBonus;

        return $bonusInfo;
    }
}