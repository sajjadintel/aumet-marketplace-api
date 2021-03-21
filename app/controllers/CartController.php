<?php

class CartController extends MainController
{

    function postAddProduct()
    {
        if (!isset($this->requestData->productId) || !$this->requestData->productId)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_productId')), null);
        $productId = $this->requestData->productId;

        if (!isset($this->requestData->quantity) || !$this->requestData->quantity)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_quantity')), null);
        $quantity = $this->requestData->quantity;

        if (!isset($this->requestData->entityId) || !$this->requestData->entityId)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_id')), null);
        $entityId = $this->requestData->entityId;

        if (!is_numeric($quantity) || $quantity < 1) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_quantity')), null);
        }
        $quantity = (int) $quantity;

        if (!is_numeric($entityId) || !array_key_exists($entityId, $this->objEntityList)) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_entityId')), null);
        }
        $entityId = (int) $entityId;

        $dbEntityProduct = new GenericModel($this->db, "entityProductSell");
        $dbEntityProduct->getWhere("productId=$productId");

        if ($dbEntityProduct->dry()) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_product')), null);
        }

        $dbAccount = new GenericModel($this->db, "account");
        $dbAccount->getWhere("entityId=$entityId");

        if ($dbAccount->dry()) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_account')), null);
        }

        $dbCartDetail = new GenericModel($this->db, "cartDetail");
        $dbCartDetail->getWhere("entityProductId = $dbEntityProduct->id and accountId=" . $this->objUser->accountId);
        $dbCartDetail->accountId = $dbAccount->id;
        $dbCartDetail->entityProductId = $dbEntityProduct->id;
        $dbCartDetail->userId = $this->objUser->id;
        $dbCartDetail->unitPrice = $dbEntityProduct->unitPrice;
        $dbCartDetail->quantity = $quantity;
        $total = $quantity;

        ## TODO: Fix Bonus logic
        // if ($dbEntityProduct->bonusTypeId == 2) {
        //     $dbBonus = new GenericModel($this->db, "entityProductSellBonusDetail");
        //     $arrBonus = $dbBonus->findWhere("entityProductId = '$dbEntityProduct->id' AND isActive = 1", 'minOrder DESC');

        //     $entityProductBonusType = new GenericModel($this->db, "entityProductBonusType");
        //     $entityProductBonusType->getWhere("id = '$dbEntityProduct->bonusTypeId'");

        //     $quantityFree = $this->calculateBonus($dbCartDetail->quantity, $arrBonus, $entityProductBonusType->formula);
        //     $dbCartDetail->quantityFree = $quantityFree;
        //     $total = $quantityFree + $quantity;
        // }

        ## TODO: To check stock and maxOrderQuantity (as per Marketplace Web logic)
        if ($total > $dbEntityProduct->stock) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_lowStock', $dbEntityProduct->stock), null);
        }

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

    private function calculateBonus($quantity, $bonuses, $formula)
    {
        foreach ($bonuses as $bonus) {
            if ($quantity >= $bonus['minOrder']) {
                $formula = str_replace('quantity', $quantity, $formula);
                $formula = str_replace('minOrder', $bonus['minOrder'], $formula);
                $formula = str_replace('bonus', $bonus['bonus'], $formula);
                if (strpos($formula, ';') === false) {
                    $formula .= ';';
                }
                $formula = '$response = ' . $formula;
                eval($formula);
                return $response;
            }
        }
        return 0;
    }

    function postDeleteItem()
    {
        if (!$this->requestData->cartItemId)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_cartItemId')), null);
        $cartItemId = $this->requestData->cartItemId;

        $dbCartDetail = new GenericModel($this->db, "cartDetail");
        $dbCartDetail->getWhere("id = '{$cartItemId}' AND accountId = '{$this->objUser->accountId}'");

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

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_deleted', $this->f3->get('RESPONSE.entity_cartItem')), $user);
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

        // Set payment methods
        $dbPaymentMethod = new GenericModel($this->db, "paymentMethod");
        $nameField = "name_" . $this->objUser->language;
        $dbPaymentMethod->name = $nameField;
        $arrPaymentMethod = $dbPaymentMethod->findAll();
        $mapPaymentMethodIdName = [];
        foreach ($arrPaymentMethod as $paymentMethod) {
            $mapPaymentMethodIdName[$paymentMethod['id']] = $paymentMethod['name'];
        }

        $dbEntityPaymentMethod = new GenericModel($this->db, "entityPaymentMethod");
        $mapSellerIdArrPaymentMethod = [];
        foreach ($allSellers as $seller) {
            $dbEntityPaymentMethod->getWhere("entityId=" . $seller->sellerId);
            $arrEntityPaymentMethod = [];
            while (!$dbEntityPaymentMethod->dry()) {
                $paymentMethod = new stdClass();
                $paymentMethod->id = $dbEntityPaymentMethod['paymentMethodId'];
                $paymentMethod->name = $mapPaymentMethodIdName[$dbEntityPaymentMethod['paymentMethodId']];

                array_push($arrEntityPaymentMethod, $paymentMethod);
                $dbEntityPaymentMethod->next();
            }

            $mapSellerIdArrPaymentMethod[$seller->sellerId] = $arrEntityPaymentMethod;
        }
        $data['mapSellerIdArrPaymentMethod'] = $mapSellerIdArrPaymentMethod;


        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_cartItems')), $data);
    }

    public function getCartItemsV2()
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

        // Set payment methods
        $dbPaymentMethod = new GenericModel($this->db, "paymentMethod");
        $nameField = "name_" . $this->objUser->language;
        $dbPaymentMethod->name = $nameField;
        $arrPaymentMethod = $dbPaymentMethod->findAll();
        $mapPaymentMethodIdName = [];
        foreach ($arrPaymentMethod as $paymentMethod) {
            $mapPaymentMethodIdName[$paymentMethod['id']] = $paymentMethod['name'];
        }

        $dbEntityPaymentMethod = new GenericModel($this->db, "entityPaymentMethod");
        $mapSellerIdArrPaymentMethod = [];
        foreach ($allSellers as $seller) {
            $dbEntityPaymentMethod->getWhere("entityId=" . $seller->sellerId);
            $arrEntityPaymentMethod = [];
            while (!$dbEntityPaymentMethod->dry()) {
                $paymentMethod = new stdClass();
                $paymentMethod->id = $dbEntityPaymentMethod['paymentMethodId'];
                $paymentMethod->name = $mapPaymentMethodIdName[$dbEntityPaymentMethod['paymentMethodId']];

                array_push($arrEntityPaymentMethod, $paymentMethod);
                $dbEntityPaymentMethod->next();
            }

            $mapSellerIdArrPaymentMethod[$seller->sellerId] = $arrEntityPaymentMethod;
        }
        $data['mapSellerIdArrPaymentMethod'] = $mapSellerIdArrPaymentMethod;


        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_cartItems')), $data);
    }
}
