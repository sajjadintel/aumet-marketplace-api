<?php

class OrderController extends MainController {

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

        for ($i = 0; $i < count($orders); $i++) {
            $arrOrderDetail = $dbOrderDetail->findWhere("id = '{$orders[$i]['id']}'");
            $orders[$i]['items'] = $arrOrderDetail;
        }

        $response['data'] = $orders;
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_order')), $response);
    }

    public function getOrder()
    {
        if (!$this->f3->get('PARAMS.id') || !is_numeric($this->f3->get('PARAMS.id')))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_orderId')), null);

        $orderId = $this->f3->get('PARAMS.id');

        $arrEntityId = Helper::idListFromArray($this->objEntityList);

        $dbOrder = new GenericModel($this->db, "vwOrderEntityUser");
        $order = $dbOrder->findWhere("id = '$orderId' AND entityBuyerId IN ($arrEntityId)");

        if (count($order) == 0)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_order')), null);

        $order = $order[0];

        $dbOrderDetail = new GenericModel($this->db, "vwOrderDetail");
        $dbOrderDetail->productName = "productName" . ucfirst($this->language);

        $arrOrderDetail = $dbOrderDetail->findWhere("id = '$orderId'");
        $order['items'] = $arrOrderDetail;

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_order')), $order);
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
            $note = $cartDetail->note;
            $quantityFree = $cartDetail->quantityFree;
            $unitPrice = $cartDetail->unitPrice;

            $query = "INSERT INTO orderDetail (`orderId`, `entityProductId`, `quantity`, `note`, `quantityFree`, `unitPrice`) VALUES ('" . $orderId . "', '" . $entityProductId . "', '" . $quantity . "', '" . $note . "', '" . $quantityFree . "', '" . $unitPrice . "');";
            array_push($commands, $query);
        }

        $this->db->exec($commands);

        $dbCartDetail = new GenericModel($this->db, "cartDetail");
        $dbCartDetail->erase("accountId=". $this->objUser->accountId);

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

    private function checkForProductsDuplication($missingProducts, $fieldId = "itemId")
    {
        $dupe_array = array();
        foreach ($missingProducts as $val) {
            if (!isset($dupe_array[$val->$fieldId])) {
                $dupe_array[$val->$fieldId] = 1;
                continue;
            }
            if (++$dupe_array[$val->$fieldId] > 1) {
                return true;
            }
        }
        return false;
    }

    public function postOrderEdit()
    {
        // Check if body is missing mandatory fields
        if (!isset($this->requestData->orderId) || !$this->requestData->orderId || !is_numeric($this->requestData->orderId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_orderId')), null);
        if (!isset($this->requestData->products) || !$this->requestData->products)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_products')), null);

        // Check if orderId is valid
        $orderId = $this->requestData->orderId;

        $dbOrder = new GenericModel($this->db, "order");
        $dbOrder->getWhere("id = '$orderId'");

        if ($dbOrder->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_order')), null);

        if($dbOrder['statusId'] != Constants::ORDER_STATUS_PENDING)
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_order')), null);

        $entitySellerId = $dbOrder['entitySellerId'];

        // Check if products are valid
        $arrProducts = $this->requestData->products;
        $valid = is_array($arrProducts);

        // Check for missing id or quantity
        if($valid) {
            $arrProductId = [];
            $mapProductIdQuantity = [];
            foreach($arrProducts as $product) {
                if((!isset($product->productId) || !$product->productId || !is_numeric($product->productId))
                    || (!isset($product->quantity) || !$product->quantity || !is_numeric($product->quantity))) {
                    $valid = false;
                    break;
                }
                $mapProductIdQuantity[$product->productId] = $product->quantity;
                array_push($arrProductId, $product->productId);
            }
        }
        
        // Check for product duplicates
        $valid = $valid && !$this->checkForProductsDuplication($arrProducts, "productId");

        $arrOrderProducts = [];
        $subtotal = 0;
        $vat = 0;
        if($valid) {
            // Check if all ids are valid
            $dbProducts = new GenericModel($this->db, "vwEntityProductSell");
            $arrProductIdStr = implode(",", $arrProductId);
            $arrProductsDb = $dbProducts->findWhere("productId IN ($arrProductIdStr)");
            if(count($arrProductsDb) == count($arrProductId)) {
                foreach($arrProductsDb as $productsDb) {
                    $entityProductId = $productsDb['id'];
                    $productId = $productsDb['productId'];
                    $entityId = $productsDb['entityId'];
                    
                    // Check if quantity higher than availableQuantity or lower than minimumOrderQuantity or if product is from different distributor
                    $quantity = $mapProductIdQuantity[$productId];
                    $minimumOrderQuantity = $productsDb['minimumOrderQuantity']; 
                    $availableQuantity = ProductHelper::getAvailableQuantity($productsDb['stock'], $productsDb['maximumOrderQuantity']);
                    if($quantity > $availableQuantity
                        || ($minimumOrderQuantity && $quantity < $minimumOrderQuantity)
                        || $entityId != $entitySellerId) {
                        $valid = false;
                        break;
                    }
                    
                    // Get freeQuantity
                    $bonusInfo = ProductHelper::getBonusInfo(
                        $this->db,
                        $this->language,
                        $this->objEntityList,
                        $entityProductId,
                        $entityId,
                        $availableQuantity,
                        $quantity
                    );
                    $quantityFree = $bonusInfo->activeBonus->totalBonus;

                    $orderProduct = new stdClass();
                    $orderProduct->entityProductId = $entityProductId;
                    $orderProduct->quantity = $quantity;
                    $orderProduct->quantityFree = $quantityFree;
                    $orderProduct->unitPrice = $productsDb['unitPrice'];
                    $orderProduct->tax = $productsDb['vat'];
                    $orderProduct->totalQuantity = $quantity + $quantityFree;
                    $orderProduct->freeRatio = $quantityFree / ($quantity + $quantityFree);
                    $orderProduct->currency = $productsDb['currency'];
                    $orderProduct->image = $productsDb['image'];
                    $orderProduct->name = $productsDb["productName_" . $this->language];
                    array_push($arrOrderProducts, $orderProduct);
                    
                    $productPrice = $quantity * $productsDb['unitPrice'];
                    $subtotal += $productPrice;
                    $vat += $productPrice * $productsDb['vat'] / 100;
                }
            } else {
                $valid = false;
            }
        }

        if (!$valid) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_products')), null);
        }

        $total = $subtotal + $vat;

        // Update the relation
        $dbRelation = new GenericModel($this->db, "entityRelation");
        $dbRelation->getWhere("entityBuyerId = $dbOrder->entityBuyerId AND entitySellerId = $dbOrder->entitySellerId");

        if ($dbRelation->dry()) {
            $dbRelation->entityBuyerId = $dbOrder->entityBuyerId;
            $dbRelation->entitySellerId = $dbOrder->entitySellerId;
            $dbRelation->currencyId = $dbOrder->currencyId;
            $dbRelation->orderCount = 1;
            $dbRelation->orderTotal = $total;
            $dbRelation->add();
        } else {
            $dbRelation->orderTotal -= $dbOrder->total;
            $dbRelation->orderTotal += $total;
            $dbRelation->updatedAt = date('Y-m-d H:i:s');
            $dbRelation->update();
        }

        // Remove old orderDetail
        $dbOrderDetail = new GenericModel($this->db, "orderDetail");
        $dbOrderDetail->getWhere("orderId = $orderId");
        while (!$dbOrderDetail->dry()) {
            $dbOrderDetail->delete();
            $dbOrderDetail->next();
        }

        // Add orderDetail
        foreach($arrOrderProducts as $orderProduct) {
            $dbOrderDetail->orderId = $dbOrder->id;
            $dbOrderDetail->entityProductId = $orderProduct->entityProductId;
            $dbOrderDetail->quantity = $orderProduct->quantity;
            $dbOrderDetail->quantityFree = $orderProduct->quantityFree;
            $dbOrderDetail->unitPrice = $orderProduct->unitPrice;
            $dbOrderDetail->tax = $orderProduct->tax;
            $dbOrderDetail->totalQuantity = $orderProduct->totalQuantity;
            $dbOrderDetail->requestedQuantity = $orderProduct->totalQuantity;
            $dbOrderDetail->shippedQuantity = $orderProduct->totalQuantity;
            $dbOrderDetail->freeRatio = $orderProduct->freeRatio;
            if(isset($this->requestData->note))
                $dbOrderDetail->note = $this->requestData->note;

            $dbOrderDetail->add();
        }

        // Update the order
        if(isset($this->requestData->note))
            $dbOrder->note = $this->requestData->note;

        $dbOrder->subtotal = $subtotal;
        $dbOrder->vat = $vat;
        $dbOrder->total = $total;
        $dbOrder->updateDateTime = $dbOrder->getCurrentDateTime();

        if ($dbOrder->edit()) {
            // Send mails to notify about order update
            $emailHandler = new EmailHandler($this->db);
            $emailFile = "emails/layout.php";
            $this->f3->set('domainUrl', getenv('DOMAIN_URL'));
            $this->f3->set('title', 'Order Update');
            $this->f3->set('emailType', 'orderUpdate');

            $orderStatusUpdateTitle = "Order #" . $dbOrder->id . " updated";
            $this->f3->set('orderUpdateTitle', $orderStatusUpdateTitle);

            $dbCurrency = new GenericModel($this->db, "currency");
            $dbCurrency->getWhere("id = $dbOrder->currencyId");

            $this->f3->set('products', $arrOrderProducts);
            $this->f3->set('currencySymbol', $dbCurrency->symbol);
            $this->f3->set('subTotal', $dbOrder->subtotal);
            $this->f3->set('tax', $dbOrder->vat);
            $this->f3->set('total', $dbOrder->total);

            $ordersUrl = "web/pharmacy/order/history";
            $this->f3->set('ordersUrl', $ordersUrl);

            $htmlContent = View::instance()->render($emailFile);

            $dbEntityUserProfile = new GenericModel($this->db, "vwEntityUserProfile");

            $arrEntityUserProfile = $dbEntityUserProfile->getByField("entityId", $dbOrder->entityBuyerId);
            foreach ($arrEntityUserProfile as $entityUserProfile) {
                $emailHandler->appendToAddress($entityUserProfile->userEmail, $entityUserProfile->userFullName);
            }

            $subject = "Order Update";
            if (getenv('ENV') != Constants::ENV_PROD) {
                $subject .= " - (Test: " . getenv('ENV') . ")";
                if (getenv('ENV') == Constants::ENV_LOCAL) {
                    $emailHandler->resetTos();
                    $emailHandler->appendToAddress("carl8smith94@gmail.com", "Antoine Abou Cherfane");
                    $emailHandler->appendToAddress("patrick.younes.1.py@gmail.com", "Patrick");
                }
            }
            $emailHandler->sendEmail(Constants::EMAIL_ORDER_UPDATE, $subject, $htmlContent);
            $emailHandler->resetTos();

            $ordersUrl = "web/distributor/order/new";
            $this->f3->set('ordersUrl', $ordersUrl);

            $arrEntityUserProfile = $dbEntityUserProfile->getByField("entityId", $dbOrder->entitySellerId);
            foreach ($arrEntityUserProfile as $entityUserProfile) {
                $emailHandler->appendToAddress($entityUserProfile->userEmail, $entityUserProfile->userFullName);
            }

            $subject = "Order Update";
            if (getenv('ENV') != Constants::ENV_PROD) {
                $subject .= " - (Test: " . getenv('ENV') . ")";
                if (getenv('ENV') == Constants::ENV_LOCAL) {
                    $emailHandler->resetTos();
                    $emailHandler->appendToAddress("carl8smith94@gmail.com", "Antoine Abou Cherfane");
                    $emailHandler->appendToAddress("patrick.younes.1.py@gmail.com", "Patrick");
                }
            }
            $emailHandler->sendEmail(Constants::EMAIL_ORDER_UPDATE, $subject, $htmlContent);
            
            $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_updated', $this->f3->get('RESPONSE.entity_order')), null);
        } else {
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbOrder->exception, null);
        }
    }
}
