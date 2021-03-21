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
        if (!isset($this->requestData->detailsSeller))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_detailsSeller')), null);
        $detailsSeller = $this->requestData->detailsSeller;

        if (!isset($this->requestData->detailsBuyer))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_detailsBuyer')), null);
        $detailsBuyer = $this->requestData->detailsBuyer;

        $dbEntityPaymentMethod = new GenericModel($this->db, "entityPaymentMethod");
        $dbCurrencies = new GenericModel($this->db, "currency");
        $dbEntities = new GenericModel($this->db, "entity");
        $dbAccount = new GenericModel($this->db, "account");
        $dbEntityBranch = new GenericModel($this->db, "entityBranch");
        $dbCartDetail = new GenericModel($this->db, "cartDetail");
        $dbOrder = new GenericModel($this->db, "order");

        $dbVwCartDetail = new GenericModel($this->db, "vwCartDetail");
        $dbVwCartDetail->entityName = "entityName_" . $this->language;
        $dbVwCartDetail->stockStatusName = "stockStatusName_" . $this->language;
        $dbVwCartDetail->madeInCountryName = "madeInCountryName_" . $this->language;
        $dbVwCartDetail->productName = "productName_" . $this->language;


        // Get Buyer Details
        $buyers = array();
        foreach ($detailsBuyer as $buyerItem) {
            $buyer = array();

            // validate entityId
            if (!isset($buyerItem->entityId) || !is_numeric($buyerItem->entityId))
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_detailsBuyer') . " - " . $this->f3->get('RESPONSE.entity_entityId')), null);
            $buyer['entityId'] = $buyerItem->entityId;

            // get Account details
            $dbAccount->getWhere("entityId=$buyerItem->entityId");
            if ($dbAccount->dry()) {
                $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsBuyer') . " - " . $this->f3->get('RESPONSE.entity_account')), null);
            }
            $buyer['accountId'] = $dbAccount->id;

            // get Entity details
            $dbEntities->getWhere("id = '{$buyerItem->entityId}'");
            if ($dbEntities->dry()) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsBuyer') . " - " . $this->f3->get('RESPONSE.entity_entityId')), null);
            }

            // get Entity Branch
            $dbEntityBranch->getWhere("entityId = '{$buyerItem->entityId}'");
            if ($dbEntityBranch->dry()) {
                $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsBuyer') . " - " . $this->f3->get('RESPONSE.entity_entityBranch')), null);
            }
            $buyer['entityBranchId'] = $dbEntityBranch->id;

            // get currency id
            $dbCurrencies->getWhere("id = '{$dbEntities->currencyId}'");
            if ($dbCurrencies->dry()) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsBuyer') . " - " . $this->f3->get('RESPONSE.entity_currencyId')), null);
            }
            $buyer['currencyId'] = $dbEntities->currencyId;

            array_push($buyers, $buyer);
        }

        // Get Seller Details
        $sellers = array();
        foreach ($detailsSeller as $sellerItem) {

            $seller = array();

            // validate entityId
            if (!isset($sellerItem->entityId) || !is_numeric($sellerItem->entityId))
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_detailsSeller') . " - " . $this->f3->get('RESPONSE.entity_entityId')), null);
            $seller['entityId'] = $sellerItem->entityId;

            // get Entity details
            $dbEntities->getWhere("id = '{$sellerItem->entityId}'");
            if ($dbEntities->dry()) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsSeller') . " - " . $this->f3->get('RESPONSE.entity_entityId')), null);
            }

            // get Entity Branch
            $dbEntityBranch->getWhere("entityId = '{$sellerItem->entityId}'");
            if ($dbEntityBranch->dry()) {
                $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsSeller') . " - " . $this->f3->get('RESPONSE.entity_entityBranch')), null);
            }
            $seller['entityBranchId'] = $dbEntityBranch->id;

            // validate payment method
            if (!isset($sellerItem->paymentMethodId) || !is_numeric($sellerItem->paymentMethodId))
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_detailsSeller') . " - " . $this->f3->get('RESPONSE.entity_paymentMethodId')), null);
            $paymentMethodId = $sellerItem->paymentMethodId;

            $dbEntityPaymentMethod->getWhere("paymentMethodId = '{$paymentMethodId}' AND entityId = '{$sellerItem->entityId}'");
            if ($dbEntityPaymentMethod->dry()) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsSeller') . " - " . $this->f3->get('RESPONSE.entity_paymentMethod')), null);
            }
            $dbEntityPaymentMethod->reset();

            // add payment method
            $seller['paymentMethodId'] = $paymentMethodId;

            // add note to seller
            if (isset($sellerItem->note)) {
                $seller['note'] = $sellerItem->note;
            }

            // add products
            $seller['cartItems'] = $dbVwCartDetail->findWhere("accountId = '{$dbAccount->id}' AND entityId = '{$sellerItem->entityId}'");
            if (count($seller['cartItems']) == 0) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_detailsSeller') . " - " . $this->f3->get('RESPONSE.entity_entityId')), null);
            }
            $dbVwCartDetail->reset();

            // get currency id
            $dbCurrencies->getWhere("id = '{$dbEntities->currencyId}'");
            if ($dbCurrencies->dry()) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsBuyer') . " - " . $this->f3->get('RESPONSE.entity_currencyId')), null);
            }
            $seller['currencyId'] = $dbEntities->currencyId;

            array_push($sellers, $seller);
        }

        //////////////////////////////////

        // Add to orderGrand
        $dbOrderGrand = new GenericModel($this->db, "orderGrand");
        $dbOrderGrand->buyerEntityId =  $buyers[0]['entityId'];
        $dbOrderGrand->buyerBranchId =  $buyers[0]['entityBranchId'];
        $dbOrderGrand->buyerUserId = $this->objUser->id;
        $dbOrderGrand->addReturnID();


        foreach ($sellers as $seller) {
            $sellerEntityId = $seller['entityId'];
            $note = $seller['note'] ? $seller['note'] : null;
            $paymentMethodId = $seller['paymentMethodId'];

            // Send note
            if ($note != null && $note != '') {

                // TODO: @Sajad - CREATE A HELPER FUNCTION TO CREATE CHATROOM
                $dbChatRoom = new GenericModel($this->db, "chatroom");
                $dbChatRoom->getWhere("sellerEntityId='{$sellerEntityId}' AND buyerEntityId = '{$dbOrderGrand->buyerEntityId}'");
                if ($dbChatRoom->dry()) {
                    $dbChatRoom->sellerEntityId = $sellerEntityId;
                    $dbChatRoom->buyerEntityId = $dbOrderGrand->buyerEntityId;
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

                // TODO: @Sajad - CREATE A HELPER FUNCTION TO SEND MESSAGE
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

            $cartItems = $seller['cartItems'];

            $total = 0;
            foreach ($cartItems as $cartItem) {
                $total += $cartItem['quantity'] * $cartItem['unitPrice'];
            }

            // Add to order
            $dbOrder->orderGrandId = $dbOrderGrand->id;
            $dbOrder->entityBuyerId = $buyers[0]['entityId'];
            $dbOrder->entitySellerId = $sellerEntityId;
            $dbOrder->branchBuyerId = $buyers[0]['entityBranchId'];
            $dbOrder->branchSellerId = $seller['entityBranchId'];
            $dbOrder->userBuyerId = $this->objUser->id;
            $dbOrder->userSellerId = null;
            $dbOrder->statusId = 1;
            $dbOrder->paymentMethodId = $seller['paymentMethodId'];

            // TODO: Adjust serial logic
            $dbOrder->serial = mt_rand(100000, 999999);

            $dbOrder->currencyId = $seller['currencyId'];
            $dbOrder->subtotal = $total;
            $dbOrder->total = $total;
            $dbOrder->addReturnID();

            $arrEntityProductId = array();

            $commands = [];
            foreach ($cartItems as $cartItem) {
                $orderId = $dbOrder->id;
                $entityProductId = $cartItem['entityProductId'];
                $quantity = $cartItem['quantity'];
                $note = $cartItem['note'];
                $quantityFree = $cartItem['quantityFree'];
                $unitPrice = $cartItem['unitPrice'];
                $vat = $cartItem['vat'];
                $totalQuantity = $quantity + $quantityFree;
                $freeRatio = $quantityFree / ($quantity + $quantityFree);

                $query = "INSERT INTO orderDetail (`orderId`, `entityProductId`, `quantity`, `quantityFree`, `freeRatio`, `requestedQuantity`, `shippedQuantity`, `note`, `unitPrice`, `tax`) VALUES "
                    . "('" . $orderId . "', '" . $entityProductId . "', '" . $quantity . "', '" . $quantityFree . "', '" . $freeRatio . "', '" . $totalQuantity . "', '" . $totalQuantity . "', '" . $note . "', '" . $unitPrice . "', '" . $vat . "');";

                array_push($commands, $query);
                array_push($arrEntityProductId, $entityProductId);
            }

            $this->db->exec($commands);

            $dbCartDetail->erase("accountId = '{$buyers[0]['accountId']}' AND entityProductId IN (" . implode(", ", $arrEntityProductId) . ") ");
        }

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
