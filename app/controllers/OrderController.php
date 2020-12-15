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

        $sortBy = 'idDesc';
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
                $filter .= " AND statusId IN (4,5,6,7,8)";
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
            case "idAsc":
                $orderString = "id ASC";
                break;
            case "idDesc":
                $orderString = "id DESC";
                break;
            case "entitySellerAsc":
                $orderString = "entitySeller ASC, id ASC";
                break;
            case "entitySellerDesc":
                $orderString = "entitySeller DESC, id ASC";
                break;
            case "statusAsc":
                $orderString = "status ASC, id ASC";
                break;
            case "statusDesc":
                $orderString = "status DESC, id ASC";
                break;
            case "addedAsc":
                $orderString = "insertDateTime ASC, id ASC";
                break;
            case "addedDesc":
                $orderString = "insertDateTime DESC, id ASC";
                break;
            case "totalAsc":
                $orderString = "total ASC, id ASC";
                break;
            case "totalDesc":
                $orderString = "total DESC, id ASC";
                break;
            case "taxAsc":
                $orderString = "tax ASC, id ASC";
                break;
            case "taxDesc":
                $orderString = "tax DESC, id ASC";
                break;
            default:
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Sort')), null);
                return;
        }
        $order['order'] = $orderString;


        $genericModel = new GenericModel($this->db, "vwOrderEntityUser");
        $dataCount = $genericModel->count($filter);
        $genericModel->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $orders = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));

        $ordersWithDetail = [];
        $dbOrderDetail = new GenericModel($this->db, "vwOrderDetail");
        $dbOrderDetail->productName = "productName_" . $this->language;

        foreach ($orders as $order) {
            $arrOrderDetail = $dbOrderDetail->findWhere("id = '{$order['id']}'");
            $order['items'] = $arrOrderDetail;
            $ordersWithDetail[] = $order;
        }

        $response['data'] = $ordersWithDetail;
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_order')), $response);
    }

    public function postOrder()
    {
        // Get user account
        $dbAccount = new GenericModel($this->db, "account");
        $account = $dbAccount->getByField('id', $this->objUser->id)[0];

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

        $dbCartDetail = new GenericModel($this->db, "vwCartDetail");
        $nameField = "productName_" . $this->objUser->language;
        $dbCartDetail->name = $nameField;
        $arrCartDetail = $dbCartDetail->getByField("accountId", $this->objUser->accountId);

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

        // Group cart items by seller id
        $allCartItems = [];
        $allSellers = [];
        foreach ($arrCartDetail as $cartDetail) {
            $sellerId = $cartDetail->entityId;

            $cartItemsBySeller = [];
            if (array_key_exists($sellerId, $allCartItems)) {
                $cartItemsBySeller = $allCartItems[$sellerId];
            } else {
                $nameField = "entityName_" . $this->objUser->language;

                $seller = new stdClass();
                $seller->sellerId = $sellerId;
                array_push($allSellers, $seller);
            }

            array_push($cartItemsBySeller, $cartDetail);
            $allCartItems[$sellerId] = $cartItemsBySeller;
        }

        $mapSellerIdOrderId = [];
        foreach ($allSellers as $seller) {
            $sellerId = $seller->sellerId;
            $cartItemsBySeller = $allCartItems[$sellerId];

            $total = 0;
            foreach ($cartItemsBySeller as $cartItem) {
                $total += $cartItem->quantity * $cartItem->unitPrice;
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
            $dbOrder->paymentMethodId = 1;

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
            $orderId = $mapSellerIdOrderId[$cartDetail->entityId];
            $entityProductId = $cartDetail->entityProductId;
            $quantity = $cartDetail->quantity;
            $quantityFree = $cartDetail->quantityFree;
            $unitPrice = $cartDetail->unitPrice;

            $query = "INSERT INTO orderDetail (`orderId`, `entityProductId`, `quantity`, `quantityFree`, `unitPrice`) VALUES ('" . $orderId . "', '" . $entityProductId . "', '" . $quantity . "', '" . $quantityFree . "', '" . $unitPrice . "');";
            array_push($commands, $query);
        }

        $this->db->exec($commands);

        $dbCartDetail = new GenericModel($this->db, "cartDetail");
        $dbCartDetail->getByField("accountId", $this->objUser->accountId);
        $dbCartDetail->delete();

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_order')), null);
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
