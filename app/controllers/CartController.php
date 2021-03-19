<?php

class CartController extends MainController {
    function postAddProduct()
    {
        $productId = $this->requestData->productId ? $this->requestData->productId :
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_productId')), null);
        $quantity = $this->requestData->quantity ? (int)$this->requestData->quantity :
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_quantity')), null);

        if (!is_numeric($quantity) || $quantity < 1) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_quantity')), null);
        }

        $dbEntityProduct = new GenericModel($this->db, "entityProductSell");
        $dbEntityProduct->getWhere("productId=$productId");

        if ($dbEntityProduct->dry()) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_product')), null);
        }

        $dbCartDetail = new GenericModel($this->db, "cartDetail");
        $dbCartDetail->getWhere("entityProductId = $dbEntityProduct->id and accountId=" . $this->objUser->accountId);
        $dbCartDetail->accountId = $this->objUser->accountId;
        $dbCartDetail->entityProductId = $dbEntityProduct->id;
        $dbCartDetail->userId = $this->objUser->id;
        $dbCartDetail->quantity = $dbCartDetail->quantity + $quantity;
        $dbCartDetail->unitPrice = $dbEntityProduct->unitPrice;
        if ($dbCartDetail->dry()) {
            if (isset($this->requestData->note))
                $dbCartDetail->note = $this->requestData->note;

            if (!$dbCartDetail->add()) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.403_queryError', $dbCartDetail->exception), null);
            }
        } else {
            if (!$dbCartDetail->update()) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.403_queryError', $dbCartDetail->exception), null);
            }
        }

        // Get cart count
        $arrCartDetail = $dbCartDetail->getByField("accountId", $this->objUser->accountId);
        $this->objUser->cartCount = count($arrCartDetail);

        $user = new UserProfile($this->objUser, $this->objEntityList, $this->accessToken);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_cartItem')), $user);
    }

    function postAddBonus()
    {
        $bonusId = $this->requestData->bonusId ? $this->requestData->bonusId :
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_bonusId')), null);


        $dbBonus = new GenericModel($this->db, "entityProductSellBonusDetail");
        $dbBonus->getWhere("id = $bonusId AND isActive = 1");

        if ($dbBonus->dry()) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_bonus')), null);
        }

        $dbEntityProduct = new GenericModel($this->db, "entityProductSell");
        $dbEntityProduct->getWhere("productId=$dbBonus->entityProductId");

        if ($dbEntityProduct->dry()) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_product')), null);
        }

        $dbCartDetail = new GenericModel($this->db, "cartDetail");
        $dbCartDetail->accountId = $this->objUser->accountId;
        $dbCartDetail->entityProductId = $dbEntityProduct->id;
        $dbCartDetail->userId = $this->objUser->id;
        $dbCartDetail->quantity = $dbBonus->minOrder;
        $dbCartDetail->quantityFree = $dbBonus->bonus;
        $dbCartDetail->unitPrice = $dbEntityProduct->unitPrice;
        if (!$dbCartDetail->add()) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.403_queryError', $dbCartDetail->exception), null);
        }

        // Get cart count
        $arrCartDetail = $dbCartDetail->getByField("accountId", $this->objUser->accountId);
        $this->objUser->cartCount = count($arrCartDetail);

        $user = new UserProfile($this->objUser, $this->objEntityList, $this->accessToken);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_cartItem')), $user);
    }

    function postDeleteItem()
    {
        $itemId = $this->requestData->itemId ? $this->requestData->itemId :
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_itemId')), null);

        $dbCartDetail = new GenericModel($this->db, "cartDetail");
        $dbCartDetail->getWhere("id = '{$itemId}' AND accountId = '{$this->objUser->accountId}'");

        if ($dbCartDetail->dry()) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_item')), null);
        }

        if (!$dbCartDetail->delete()) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.403_queryError', $dbCartDetail->exception), null);
        }

        // Get cart count
        $arrCartDetail = $dbCartDetail->getByField("accountId", $this->objUser->accountId);
        $this->objUser->cartCount = count($arrCartDetail);

        $user = new UserProfile($this->objUser, $this->objEntityList, $this->accessToken);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_cartItem')), $user);
    }

    public function getCartItems()
    {
        $dbCartDetail = new GenericModel($this->db, "vwCartDetail");
        $dbCartDetail->entityName = "entityName_" . $this->language;
        $dbCartDetail->stockStatusName = "stockStatusName_" . $this->language;
        $dbCartDetail->madeInCountryName = "madeInCountryName_" . $this->language;
        $dbCartDetail->productName = "productName_" . $this->language;

        $arrCartDetail = $dbCartDetail->findWhere("accountId = " . $this->objUser->accountId);

        // Group cart items by seller id
        $allCartItems = [];
        $allSellers = [];
        foreach ($arrCartDetail as $cartDetail) {
            $sellerId = $cartDetail['entityId'];

            $cartItemsBySeller = [];
            if (array_key_exists($sellerId, $allCartItems)) {
                $cartItemsBySeller = $allCartItems[$sellerId];
            } else {
                $nameField = "entityName_" . $this->objUser->language;

                $seller = new stdClass();
                $seller->sellerId = $sellerId;
                $seller->name = $cartDetail[$nameField];
                array_push($allSellers, $seller);
            }

            $availableQuantity = ProductHelper::getAvailableQuantity($cartDetail['stock'], $cartDetail['maximumOrderQuantity']);
            $bonusInfo = ProductHelper::getBonusInfo(
                $this->db,
                $this->language,
                $this->objEntityList,
                $cartDetail['entityProductId'],
                $cartDetail['entityId'],
                $availableQuantity,
                $cartDetail['quantity']
            );
            $cartDetail['arrBonus'] = $bonusInfo->arrBonus;
            $cartDetail['activeBonus'] = $bonusInfo->activeBonus;

            array_push($cartItemsBySeller, $cartDetail);
            $allCartItems[$sellerId] = $cartItemsBySeller;
        }
        $data['allCartItems'] = $allCartItems;
        $data['allSellers'] = $allSellers;

        // Get all currencies
        $dbCurrencies = new GenericModel($this->db, "currency");
        $allCurrencies = $dbCurrencies->all();

        $mapCurrencyIdCurrency = [];
        foreach ($allCurrencies as $currency) {
            $currencyObj = new stdClass();
            $currencyObj->id = $currency->id;
            $currencyObj->symbol = $currency->symbol;
            $currencyObj->conversionToUSD = $currency->conversionToUSD;

            $mapCurrencyIdCurrency[$currency->id] = $currencyObj;
        }
        $data['mapCurrencyIdCurrency'] = $mapCurrencyIdCurrency;

        // Get currency by entity
        $dbEntities = new GenericModel($this->db, "entity");
        $allEntities = $dbEntities->all();

        $mapSellerIdCurrency = [];
        foreach ($allEntities as $entity) {
            $mapSellerIdCurrency[$entity->id] = $mapCurrencyIdCurrency[$entity->currencyId];
        }
        $data['mapSellerIdCurrency'] = $mapSellerIdCurrency;

        // Set buyer currency
        $dbAccount = new GenericModel($this->db, "account");
        $account = $dbAccount->getByField('id', $this->objUser->accountId)[0];
        $buyerCurrency = $mapSellerIdCurrency[$account->entityId];
        $data['buyerCurrency'] = $buyerCurrency;


        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_cartItems')), $data);
    }

}
