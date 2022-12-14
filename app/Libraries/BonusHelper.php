<?php

namespace App\Libraries;

use Base;
use DB\SQL;
use stdClass;
use GenericModel;
use Constants;

class BonusHelper {

    /**
     * calculateBonusQuantity
     *
     * @param Base $f3 f3 instance
     * @param SQL $dbConnection db connection instance
     * @param string $language user language
     * @param int $entityProductId product id
     * @param int $quantity quantity
     * @param boolean $isTotalQuantity is total quantity
     *
     * @return stdClass bonusDetail
     */
    public static function calculateBonusQuantity($f3, $dbConnection, $language, $entityProductId, $quantity, $entityIds, $isTotalQuantity = false): stdClass
    {
        $dbEntityProduct = new GenericModel($dbConnection, "vwEntityProductSell");
        $dbEntityProduct->getWhere("id=$entityProductId");
        $productId = $dbEntityProduct->id;
        $sellerId = $dbEntityProduct->entityId;

        $maxOrder = min($dbEntityProduct->stock, $dbEntityProduct->maximumOrderQuantity);

        if (!$dbEntityProduct->maximumOrderQuantity)
            $maxOrder = $dbEntityProduct->stock;
        if (!$dbEntityProduct->stock)
            $maxOrder = 0;

        if ($isTotalQuantity) {
            if ($quantity > $maxOrder) {
                $quantity = $maxOrder;
            }
        }

        // Get all related bonuses
        $mapBonusIdRelationGroup = [];
        $mapSellerIdRelationGroupId = [];
        $dbBonus = new GenericModel($dbConnection, "vwEntityProductSellBonusDetail");
        $dbBonus->bonusTypeName = "bonusTypeName_" . $language;
        $arrBonus = $dbBonus->getWhere("entityProductId = $productId AND isActive = 1");
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

        $arrEntityId = implode(',', $entityIds);
        $dbEntityRelation = new GenericModel($dbConnection, "entityRelation");
        $arrEntityRelation = $dbEntityRelation->getWhere("entityBuyerId IN ($arrEntityId)");
        foreach ($arrEntityRelation as $entityRelation) {
            $mapSellerIdRelationGroupId[$entityRelation['entitySellerId']] = $entityRelation['relationGroupId'];
        }

        $quantityFree = 0;
        $activeBonus = new stdClass();
        $activeBonus->totalBonus = 0;
        foreach ($arrBonus as $bonus) {
            $bonusId = $bonus['id'];

            // Check if bonus available for buyer
            $valid = false;
            if (array_key_exists($bonusId, $mapBonusIdRelationGroup)) {
                $arrRelationGroup = $mapBonusIdRelationGroup[$bonusId];
                if (array_key_exists($sellerId, $mapSellerIdRelationGroupId)) {
                    $relationGroupId = $mapSellerIdRelationGroupId[$sellerId];
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
            if ($totalOrder > $maxOrder) {
                continue;
            }
            // if it's total quantity total (max bonus value) should be less than quantity
            if ($isTotalQuantity) {
                if ($totalOrder > $quantity) {
                    continue;
                }
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
                $activeBonus->bonusTypeId = $bonusTypeId;
                $quantityFree = $totalBonus;
            }
        }

        $bonusDetail = new stdClass();
        $bonusDetail->quantityFree = $quantityFree;
        $bonusDetail->quantity = $quantity;
        $bonusDetail->total = $quantity + $quantityFree;
        $bonusDetail->maxOrder = $maxOrder;
        $bonusDetail->activeBonus = $activeBonus;
        $bonusDetail->arrBonus = $arrBonus;
        $bonusDetail->dbEntityProduct = $dbEntityProduct;

        // if it's total quantity change max order with right value and consider bonus
        if ($isTotalQuantity) {
            switch ($bonusDetail->activeBonus->bonusTypeId) {
                case Constants::BONUS_TYPE_FIXED:
                    $bonusDetail->maxOrder = $bonusDetail->maxOrder - $bonusDetail->activeBonus->bonuses;
                    break;
                case Constants::BONUS_TYPE_DYNAMIC:
                    $max = 0;
                    for ($quantity = $maxOrder; $quantity > 0; $quantity--) {
                        $res = $quantity + floor($quantity / $bonusDetail->activeBonus->minQty) * $bonusDetail->activeBonus->bonuses;
                        if ($res <= $maxOrder) {
                            $max = $quantity;
                            break;
                        }
                    }
                    $bonusDetail->maxOrder = $max;
                    break;
                case Constants::BONUS_TYPE_PERCENTAGE:
                    $bonuses = str_replace('%', '', $bonusDetail->activeBonus->bonuses);
                    $bonusDetail->maxOrder = floor($bonusDetail->maxOrder * 100 / ((float)$bonuses + 100));
                    break;
            }
        }

        return $bonusDetail;
    }

}
