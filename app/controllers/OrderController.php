<?php

class OrderController extends MainController
{

    public function getOrders()
    {
        $type = 'all';
        if (isset($_GET['type']))
            $type = $_GET['type'];

        $limit = 10;
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];
        $order['limit'] = $limit;
        if (!is_numeric($limit))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Limit')), null);

        $offset = 0;
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        $order['offset'] = $offset;
        if (!is_numeric($offset))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Offset')), null);

        $sortBy = 'id_desc';
        if (isset($_GET['sort']))
            $sortBy = $_GET['sort'];
        $order['order'] = $sortBy;


        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $filter = "entityBuyerId IN ($arrEntityId)";

        switch ($type) {
            case 'unpaid':
                $filter .= " AND statusId IN (6,8) ";
                break;
            case 'pending':
                $filter .= " AND statusId IN (2,3)";
                break;
            case 'history':
                $filter .= " AND statusId IN (1,4,5,6,7,8,9)";
                break;
            case 'pendingFeedback':
                $filter .= " AND feedbackSubmitted = 1 AND statusId IN (6,7)";
                break;
            case 'all':
                break;
            default:
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_type')), null);
                return;
        }


        switch ($sortBy) {
            case "rand":
                $orderString = "rand()";
                break;
            case "id_aesc":
                $orderString = "id ASC";
                break;
            case "id_desc":
                $orderString = "id DESC";
                break;
            case "entity_seller_aesc":
                $orderString = "entitySeller ASC, id ASC";
                break;
            case "entity_seller_desc":
                $orderString = "entitySeller DESC, id ASC";
                break;
            case "status_aesc":
                $orderString = "status ASC, id ASC";
                break;
            case "status_desc":
                $orderString = "status DESC, id ASC";
                break;
            case "added_aesc":
                $orderString = "insertDateTime ASC, id ASC";
                break;
            case "added_desc":
                $orderString = "insertDateTime DESC, id ASC";
                break;
            case "total_aesc":
                $orderString = "total ASC, id ASC";
                break;
            case "total_desc":
                $orderString = "total DESC, id ASC";
                break;
            case "tax_aesc":
                $orderString = "tax ASC, id ASC";
                break;
            case "tax_desc":
                $orderString = "tax DESC, id ASC";
                break;
            default:
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Sort')), null);
                return;
        }
        $order['order'] = $orderString;


        $genericModel = new GenericModel($this->db, "vwOrderEntityUser");
        $genericModel->orderPaymentMethodName = "orderPaymentMethodName_" . $this->language;
        $dataCount = $genericModel->count($filter);
        $genericModel->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $orders = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));

        $dbOrderDetail = new GenericModel($this->db, "vwOrderDetail");
        $dbOrderDetail->productName = "productName" . ucfirst($this->language);
        $dbOrderDetail->paymentMethodName = "paymentMethodName_" . $this->language;

        for ($i = 0; $i < count($orders); $i++) {
            $arrOrderDetail = $dbOrderDetail->findWhere("id = '{$orders[$i]['id']}'");
            $orders[$i]['items'] = $arrOrderDetail;
        }

        $orders = Helper::addEditableOrders($orders);
        $orders = Helper::addCancellableOrders($orders);

        $response['data'] = $orders;
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_order')), $response);
    }


    public function postOrder()
    {
        ##<<<<<<< feature/MPA-67
        $sellers = [];
        if ($this->requestData->sellers)
            $sellers = $this->requestData->sellers;
        ##=======
        if (!isset($this->requestData->mapSellerIdPaymentMethodId)) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_mapSellerIdPaymentMethodId')), null);
        }

        $mapSellerIdPaymentMethodId = (array) $this->requestData->mapSellerIdPaymentMethodId;

        // Get all sellers    
        $dbCartDetail = new GenericModel($this->db, "vwCartDetail");
        $dbCartDetail->entityName = "entityName_" . $this->language;
        $dbCartDetail->stockStatusName = "stockStatusName_" . $this->language;
        $dbCartDetail->madeInCountryName = "madeInCountryName_" . $this->language;
        $dbCartDetail->productName = "productName_" . $this->language;

        $arrCartDetail = $dbCartDetail->findWhere("accountId = " . $this->objUser->accountId);

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

            array_push($cartItemsBySeller, $cartDetail);
            $allCartItems[$sellerId] = $cartItemsBySeller;
        }

        // Get all payment methods by seller
        $dbEntityPaymentMethod = new GenericModel($this->db, "entityPaymentMethod");
        $mapSellerIdArrPaymentMethod = [];
        foreach ($allSellers as $seller) {
            $dbEntityPaymentMethod->getWhere("entityId=" . $seller->sellerId);
            $arrEntityPaymentMethod = [];
            while (!$dbEntityPaymentMethod->dry()) {
                array_push($arrEntityPaymentMethod, $dbEntityPaymentMethod['paymentMethodId']);
                $dbEntityPaymentMethod->next();
            }

            $mapSellerIdArrPaymentMethod[$seller->sellerId] = $arrEntityPaymentMethod;
        }

        $validPaymentMethod = true;
        foreach ($mapSellerIdArrPaymentMethod as $sellerId => $arrPaymentMethod) {
            if (array_key_exists($sellerId, $mapSellerIdPaymentMethodId)) {
                $paymentMethodId = $mapSellerIdPaymentMethodId[$sellerId];
                if (!in_array($paymentMethodId, $arrPaymentMethod)) {
                    $validPaymentMethod = false;
                    break;
                }
            } else {
                $validPaymentMethod = false;
                break;
            }
        }

        if (!$validPaymentMethod) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_mapSellerIdPaymentMethodId')), null);
        }
        ##>>>>>>> dev

        // Get user account
        $dbAccount = new GenericModel($this->db, "account");
        $account = $dbAccount->getByField('id', $this->objUser->id)[0];
        $dbUserAccount = new GenericModel($this->db, "userAccount");

        // TODO: Adjust buyerBranchId logic
        $entityBranch = null;
        $dbEntityBranch = new GenericModel($this->db, "entityBranch");
        $branches = $dbEntityBranch->getByField("entityId", $account->entityId);
        if (sizeof($branches) > 0)
            $entityBranch = $branches[0];


        // Add to orderGrand
        $dbOrderGrand = new GenericModel($this->db, "orderGrand");
        $dbOrderGrand->buyerEntityId = $account->entityId;
        $dbOrderGrand->buyerBranchId = $entityBranch->id;
        $dbOrderGrand->buyerUserId = $this->objUser->id;
        $dbOrderGrand->paymentMethodId = 1;
        $dbOrderGrand->addReturnID();

        // Get all currencies
        $dbCurrencies = new GenericModel($this->db, "currency");
        $allCurrencies = $dbCurrencies->all();

        $mapCurrencyIdCurrency = [];
        foreach ($allCurrencies as $currency) {
            $mapCurrencyIdCurrency[$currency->id] = $currency;
        }

        // Get currency by entity
        $dbEntities = new GenericModel($this->db, "entity");
        $allEntities = $dbEntities->all();

        $mapSellerIdCurrency = [];
        foreach ($allEntities as $entity) {
            $mapSellerIdCurrency[$entity->id] = $mapCurrencyIdCurrency[$entity->currencyId];
        }

        // Get buyer currency
        $dbAccount = new GenericModel($this->db, "account");
        $account = $dbAccount->getByField('id', $this->objUser->accountId)[0];
        $buyerCurrency = $mapSellerIdCurrency[$account->entityId];

        $mapSellerIdOrderId = [];
        foreach ($allSellers as $seller) {
            $sellerId = $seller->sellerId;
            $sellerParameters = $sellers->{$sellerId}->note ?? null;
            $note = $sellerParameters != null ? $sellers->{$sellerId}->note : null;
            $paymentMethodId = $sellerParameters != null ? $sellers->{$sellerId}->paymentMethodId : null;

            if ($note != null && $note != '') {
                $arrEntityId = Helper::idListFromArray($this->objEntityList);

                $dbChatRoom = new GenericModel($this->db, "chatroom");
                $dbChatRoom->getWhere("sellerEntityId={$sellerId} AND buyerEntityId IN ({$arrEntityId})");
                if ($dbChatRoom->dry()) {
                    $dbChatRoom->sellerEntityId = $sellerId;
                    $dbChatRoom->buyerEntityId = $arrEntityId[0];
                    $dbChatRoom->sellerPendingRead = 0;
                    $dbChatRoom->buyerPendingRead = 0;
                    $dbChatRoom->updatedAt = date('Y-m-d H:i:s');
                    if (!$dbChatRoom->add())
                        $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);
                }

                $dbChatRoom->sellerPendingRead++;
                $dbChatRoom->updatedAt = date('Y-m-d H:i:s');
                $dbChatRoom->archivedAt = null;

                if (!$dbChatRoom->update())
                    $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

                $dbChatMessage = new GenericModel($this->db, "chatroomDetail");
                $dbChatMessage->chatroomId = $dbChatRoom->id;
                $dbChatMessage->senderUserId = $this->objUser->id;
                $dbChatMessage->senderEntityId = $dbChatRoom->buyerEntityId;
                $dbChatMessage->receiverEntityId = $dbChatRoom->sellerEntityId;
                $dbChatMessage->type = 1;
                $dbChatMessage->content = $note;
                $dbChatMessage->isRead = 0;
                if (!$dbChatMessage->add())
                    $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatMessage->exception, null);
            }

            $cartItemsBySeller = $allCartItems[$sellerId];

            $total = 0;
            foreach ($cartItemsBySeller as $cartItem) {
                $total += $cartItem['quantity'] * $cartItem['unitPrice'];
            }

            // TODO: Adjust sellerBranchId logic
            $sellerEntityBranch = null;
            $branches = $dbEntityBranch->getByField("entityId", $sellerId);
            if (sizeof($branches) > 0)
                $sellerEntityBranch = $branches[0];

            // Add to order
            $dbOrder = new GenericModel($this->db, "order");
            $dbOrder->orderGrandId = $dbOrderGrand->id;
            $dbOrder->entityBuyerId = $account->entityId;
            $dbOrder->entitySellerId = $sellerId;
            $dbOrder->branchBuyerId = $entityBranch->id;
            $dbOrder->branchSellerId = $sellerEntityBranch != null ? $sellerEntityBranch->id : null;
            $dbOrder->userBuyerId = $this->objUser->id;
            $dbOrder->userSellerId = null;
            $dbOrder->statusId = 1;
            ##<<<<<<< feature/MPA-67
            ##=======

            $paymentMethodId = $mapSellerIdPaymentMethodId[$sellerId];
            ##>>>>>>> dev
            $dbOrder->paymentMethodId = $paymentMethodId;

            // TODO: Adjust serial logic
            $dbOrder->serial = mt_rand(100000, 999999);

            $dbOrder->currencyId = $mapSellerIdCurrency[$sellerId]->id;
            $dbOrder->subtotal = $total;
            $dbOrder->total = $total;
            $dbOrder->addReturnID();

            $mapSellerIdOrderId[$sellerId] = $dbOrder->id;
        }

        $commands = [];
        foreach ($arrCartDetail as $cartDetail) {
            $orderId = $mapSellerIdOrderId[$cartDetail['entityId']];
            $entityProductId = $cartDetail['entityProductId'];
            $quantity = $cartDetail['quantity'];
            $note = $cartDetail['note'];
            $quantityFree = $cartDetail['quantityFree'];
            $unitPrice = $cartDetail['unitPrice'];
            $vat = $cartDetail['vat'];
            $totalQuantity = $quantity + $quantityFree;
            $freeRatio = $quantityFree / ($quantity + $quantityFree);

            $query = "INSERT INTO orderDetail (`orderId`, `entityProductId`, `quantity`, `quantityFree`, `freeRatio`, `requestedQuantity`, `shippedQuantity`, `note`, `unitPrice`, `tax`) VALUES "
                . "('" . $orderId . "', '" . $entityProductId . "', '" . $quantity . "', '" . $quantityFree . "', '" . $freeRatio . "', '" . $totalQuantity . "', '" . $totalQuantity . "', '" . $note . "', '" . $unitPrice . "', '" . $vat . "');";

            array_push($commands, $query);
        }

        $this->db->exec($commands);

        $dbCartDetail = new GenericModel($this->db, "cartDetail");
        $dbCartDetail->erase("accountId=" . $this->objUser->accountId);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_order')), null);
    }

    public function postOrderCancel()
    {
        if (!$this->requestData->orderId || !is_numeric($this->requestData->orderId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_orderId')), null);

        $orderId = $this->requestData->orderId;

        $dbOrder = new GenericModel($this->db, "order");
        $dbOrder->getWhere("id = '$orderId'");

        if ($dbOrder->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_order')), null);

        $dbOrder->statusId = 9;

        if ($dbOrder->edit())
            $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_updated', $this->f3->get('RESPONSE.entity_order')), null);
        else
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbOrder->exception, null);
    }

    public function postReportMissing()
    {
        if (!$this->requestData->orderId)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_orderId')), null);
        if (!$this->requestData->items)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_missingProducts')), null);

        $orderId = $this->requestData->orderId;
        $missingProducts = $this->requestData->items;

        if ($this->checkForProductsDuplication($missingProducts)) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_missingProducts')), null);
        }

        $dbOrder = new GenericModel($this->db, "vwOrderEntityUser");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbOrder = $dbOrder->findWhere("id = '$orderId' AND entityBuyerId IN ($arrEntityId)");

        if (sizeof($dbOrder) == 0) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.403_permissionDenied', $this->f3->get('RESPONSE.entity_feedback')), null);
        }
        $dbOrder = $dbOrder[0];

        $dbOrderDetail = new GenericModel($this->db, "vwOrderDetail");
        $arrOrderDetail = $dbOrderDetail->findWhere("id = '$orderId'");


        foreach ($missingProducts as $missingProduct) {
            if (!(is_numeric($missingProduct->itemId) && $missingProduct->itemId > 0)) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_productId')), null);
            }
            $serverProduct = $this->getProductFromArrayById($missingProduct->itemId, $arrOrderDetail);
            if ($serverProduct == null)
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_item')), null);
            if ($missingProduct->quantity > $serverProduct['quantity'] || $missingProduct->quantity <= 0) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_quantity')), null);
            }
        }

        foreach ($missingProducts as $missingProduct) {
            $dbMissingProduct = new GenericModel($this->db, "orderMissingProduct");
            $dbMissingProduct->orderId = $orderId;
            $dbMissingProduct->statusId = 1;
            $dbMissingProduct->buyerUserId = $this->objUser->id;
            $dbMissingProduct->productId = $missingProduct->itemId;
            $dbMissingProduct->quantity = $missingProduct->quantity;
            $dbMissingProduct->add();
        }


        $dbOrder->statusId = 8; // Missing Products
        $dbOrder->edit();

        $missingProductsToEmail = $missingProducts;
        // TODO: Email To Distributor

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_feedback')), null);
    }

    private function getProductFromArrayById($productId, $products)
    {
        foreach ($products as $product) {
            if ($product['productCode'] == $productId)
                return $product;
        }
        return null;
    }

    private function checkForProductsDuplication($missingProducts)
    {
        $dupe_array = array();
        foreach ($missingProducts as $val) {
            if (!isset($dupe_array[$val->itemId])) {
                $dupe_array[$val->itemId] = 1;
                continue;
            }
            if (++$dupe_array[$val->itemId] > 1) {
                return true;
            }
        }
        return false;
    }
}
