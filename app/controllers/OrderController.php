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
        $orders = Helper::addColorPalette($orders);

        $response['data'] = $orders;
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_order')), $response);
    }


    public function postOrder()
    {
        ini_set('max_execution_time', 120);

        if (!isset($this->requestData->detailsSeller))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_detailsSeller')), null);
        $detailsSeller = $this->requestData->detailsSeller;

        if (!isset($this->requestData->detailsBuyer))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_detailsBuyer')), null);
        $detailsBuyer = $this->requestData->detailsBuyer;

        $dbCurrencies = new GenericModel($this->db, "currency");
        $dbAccount = new GenericModel($this->db, "account");
        $dbCartDetail = new GenericModel($this->db, "cartDetail");
        $dbOrder = new GenericModel($this->db, "order");
        $dbVwEntityUserProfile = new GenericModel($this->db, "vwEntityUserProfile");

        $dbEntityBranch = new GenericModel($this->db, "entityBranch");
        $dbEntityBranch->address = "address_" . $this->language;

        $dbEntities = new GenericModel($this->db, "entity");
        $dbEntities->name = "name_" . $this->language;

        $dbVwEntityPaymentMethod = new GenericModel($this->db, "vwEntityPaymentMethod");
        $dbVwEntityPaymentMethod->paymentMethodName = "paymentMethodName_" . $this->language;

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
            $buyer['entityName'] = $dbEntities->name;

            // get Entity Branch details
            $dbEntityBranch->getWhere("entityId = '{$buyerItem->entityId}'");
            if ($dbEntityBranch->dry()) {
                $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsBuyer') . " - " . $this->f3->get('RESPONSE.entity_entityBranch')), null);
            }
            $buyer['entityBranchId'] = $dbEntityBranch->id;
            $buyer['address'] = $dbEntityBranch->address;

            // get currency details
            $dbCurrencies->getWhere("id = '{$dbEntities->currencyId}'");
            if ($dbCurrencies->dry()) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsBuyer') . " - " . $this->f3->get('RESPONSE.entity_currencyId')), null);
            }
            $buyer['currencyId'] = $dbEntities->currencyId;
            $buyer['currencySymbol'] = $dbCurrencies->symbol;
            $buyer['conversionToUSD'] = $dbCurrencies->conversionToUSD;

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
            $seller['entityName'] = $dbEntities->name;

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

            $dbVwEntityPaymentMethod->getWhere("paymentMethodId = '{$paymentMethodId}' AND entityId = '{$sellerItem->entityId}'");
            if ($dbVwEntityPaymentMethod->dry()) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsSeller') . " - " . $this->f3->get('RESPONSE.entity_paymentMethod')), null);
            }
            $dbVwEntityPaymentMethod->reset();

            // add payment method
            $seller['paymentMethodId'] = $paymentMethodId;
            $seller['paymentMethodName'] = $dbVwEntityPaymentMethod->paymentMethodName;

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

            // get currency details
            $dbCurrencies->getWhere("id = '{$dbEntities->currencyId}'");
            if ($dbCurrencies->dry()) {
                $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_detailsBuyer') . " - " . $this->f3->get('RESPONSE.entity_currencyId')), null);
            }
            $seller['currencyId'] = $dbEntities->currencyId;
            $seller['currencySymbol'] = $dbCurrencies->symbol;

            array_push($sellers, $seller);
        }

        //////////////////////////////////

        // Add to orderGrand
        $dbOrderGrand = new GenericModel($this->db, "orderGrand");
        $dbOrderGrand->buyerEntityId = $buyers[0]['entityId'];
        $dbOrderGrand->buyerBranchId = $buyers[0]['entityBranchId'];
        $dbOrderGrand->buyerUserId = $this->objUser->id;
        $dbOrderGrand->addReturnID();

        // Create emailHandler
        $emailHandler = new EmailHandler($this->db);
        $emailFile = "emails/layout.php";
        $this->f3->set('domainUrl', getenv('DOMAIN_URL'));
        $this->f3->set('title', 'New Order');
        $this->f3->set('emailType', 'newOrder');
        $this->f3->set('orderSubmittedAt', date("Y-m-d H:i:s"));

        $arrProducts = [];
        $arrOrderId = [];
        $arrCurrencyId = [];
        $arrSellerName = [];
        $mapCurrencyIdSubTotal = [];
        $mapCurrencyIdTax = [];
        $mapCurrencyIdTotal = [];

        foreach ($sellers as $seller) {
            $sellerEntityId = $seller['entityId'];
            $note = $seller['note'] ? $seller['note'] : null;

            $cartItems = $seller['cartItems'];
            $arrCartItems = [];

            $subTotal = 0;
            $tax = 0;
            foreach ($cartItems as $cartItem) {
                $productPrice = $cartItem['quantity'] * $cartItem['unitPrice'];
                $subTotal += $productPrice;
                $tax += $productPrice * $cartItem['vat'] / 100;

                $product = new stdClass();
                $product->image = $cartItem['image'];
                $product->name = $cartItem['productName'];
                $product->quantity = $cartItem['quantity'];
                $product->quantityFree = $cartItem['quantityFree'];
                $product->unitPrice = $cartItem['unitPrice'];
                $product->currency = $cartItem['currency'];

                array_push($arrCartItems, $product);
                array_push($arrProducts, $product);
            }

            $total = $subTotal + $tax;

            $sellerCurrencyId = $seller['currencyId'];
            array_push($arrCurrencyId, $sellerCurrencyId);

            if (array_key_exists($sellerCurrencyId, $mapCurrencyIdSubTotal)) {
                $mapCurrencyIdSubTotal[$sellerCurrencyId] += $subTotal;
            } else {
                $mapCurrencyIdSubTotal[$sellerCurrencyId] = $subTotal;
            }

            if (array_key_exists($sellerCurrencyId, $mapCurrencyIdTax)) {
                $mapCurrencyIdTax[$sellerCurrencyId] += $tax;
            } else {
                $mapCurrencyIdTax[$sellerCurrencyId] = $tax;
            }

            if (array_key_exists($sellerCurrencyId, $mapCurrencyIdTotal)) {
                $mapCurrencyIdTotal[$sellerCurrencyId] += $total;
            } else {
                $mapCurrencyIdTotal[$sellerCurrencyId] = $total;
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
            $dbOrder->note = $note;

            // TODO: Adjust serial logic
            $dbOrder->serial = mt_rand(100000, 999999);

            $dbOrder->currencyId = $sellerCurrencyId;
            $dbOrder->subtotal = $subTotal;
            $dbOrder->vat = $tax;
            $dbOrder->total = $total;
            $dbOrder->addReturnID();

            // TODO: @Sajad - CREATE A HELPER FUNCTION TO CREATE CHATROOM
            $dbChatRoom = new GenericModel($this->db, "chatroom");
            $dbChatRoom->getWhere("sellerEntityId='{$sellerEntityId}' AND buyerEntityId = '{$dbOrderGrand->buyerEntityId}'");
            if ($dbChatRoom->dry()) {

                // TODO: @Sajad - Replace sellerEntityId and buyerEntityId with entitySellerId and entityBuyerId
                $dbChatRoom->sellerEntityId = $sellerEntityId;
                $dbChatRoom->buyerEntityId = $dbOrderGrand->buyerEntityId;

                // TODO: @Sajad - Replace sellerPendingRead and buyerPendingRead with pendingReadSeller and pendingReadBuyer
                $dbChatRoom->sellerPendingRead = 0;
                $dbChatRoom->buyerPendingRead = 0;
                $dbChatRoom->updatedAt = date('Y-m-d H:i:s');
                if (!$dbChatRoom->add())
                    $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);
            }

            // Send note
            if ($note != null && $note != '') {

                $dbChatRoom->sellerPendingRead++;
                $dbChatRoom->updatedAt = date('Y-m-d H:i:s');

                // TODO: @Sajad - fix archivedAt and replace with archivedSellerAt and archivedBuyerAt
                $dbChatRoom->archivedAt = null;

                if (!$dbChatRoom->update())
                    $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

                // TODO: @Sajad - CREATE A HELPER FUNCTION TO SEND MESSAGE
                $dbChatMessage = new GenericModel($this->db, "chatroomDetail");
                $dbChatMessage->chatroomId = $dbChatRoom->id;

                // TODO: @Sajad - Replace senderUserId, senderEntityId and receiverEntityId with userSenderId, entitySenderId and entityReceiverId
                $dbChatMessage->senderUserId = $this->objUser->id;
                $dbChatMessage->senderEntityId = $dbChatRoom->buyerEntityId;
                $dbChatMessage->receiverEntityId = $dbChatRoom->sellerEntityId;
                $dbChatMessage->type = 1;
                $dbChatMessage->content = "Note for Order #{$dbOrder->id}: " . $note;
                $dbChatMessage->isReadBuyer = 0;
                $dbChatMessage->isReadSeller = 0;
                if (!$dbChatMessage->add())
                    $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatMessage->exception, null);
            }

            array_push($arrOrderId, $dbOrder->id);
            array_push($arrSellerName, $seller['entityName']);

            // Send email to seller
            $this->f3->set('products', $arrCartItems);
            $this->f3->set('currencySymbol', $seller['currencySymbol']);
            $this->f3->set('subTotal', round($subTotal, 2));
            $this->f3->set('tax', round($tax, 2));
            $this->f3->set('total', round($total, 2));
            $this->f3->set('ordersUrl', "web/distributor/order/pending");
            $this->f3->set('name', "Buyer name: " . $buyers[0]['entityName']);
            $this->f3->set('buyerEmail', "Email: " . $this->objUser->email);
            $this->f3->set('buyerAddress', "Address: " . $buyers[0]['address']);
            $this->f3->set('paymentMethod', $seller['paymentMethodName']);

            $arrEntityUserProfile = $dbVwEntityUserProfile->getByField("entityId", $sellerEntityId);
            foreach ($arrEntityUserProfile as $entityUserProfile) {
                $emailHandler->appendToAddress($entityUserProfile->userEmail, $entityUserProfile->userFullName);
            }
            $htmlContent = View::instance()->render($emailFile);

            $subject = "Aumet - you've got a new order! (" . $dbOrder->id . ")";
            if (getenv('ENV') != Constants::ENV_PROD) {
                $subject .= " - (Test: " . getenv('ENV') . ")";

                if (getenv('ENV') == Constants::ENV_LOCAL) {
                    $emailHandler->resetTos();
                    $emailHandler->appendToAddress("carl8smith94@gmail.com", "Antoine Abou Cherfane");
                    $emailHandler->appendToAddress("patrick.younes.1.py@gmail.com", "Patrick");
                    $emailHandler->appendToAddress("sajjadintel@gmail.com", "Sajad");
                    $emailHandler->appendToAddress("n.javaid@aumet.com", "Naveed Javaid");
                }
            }

            $emailHandler->sendEmail(Constants::EMAIL_NEW_ORDER, $subject, $htmlContent);
            $emailHandler->resetTos();

            $arrEntityProductId = array();

            $commands = [];
            foreach ($cartItems as $cartItem) {
                $orderId = $dbOrder->id;
                $entityProductId = $cartItem['entityProductId'];
                $quantity = $cartItem['quantity'];
                $quantityFree = $cartItem['quantityFree'];
                $unitPrice = $cartItem['unitPrice'];
                $vat = $cartItem['vat'];
                $totalQuantity = $quantity + $quantityFree;
                $freeRatio = $quantityFree / ($quantity + $quantityFree);

                $query = "INSERT INTO orderDetail (`orderId`, `entityProductId`, `quantity`, `quantityFree`, `freeRatio`, `requestedQuantity`, `shippedQuantity`, `unitPrice`, `tax`) VALUES "
                    . "('" . $orderId . "', '" . $entityProductId . "', '" . $quantity . "', '" . $quantityFree . "', '" . $freeRatio . "', '" . $totalQuantity . "', '" . $totalQuantity . "', '" . $unitPrice . "', '" . $vat . "');";

                array_push($commands, $query);
                array_push($arrEntityProductId, $entityProductId);
            }

            $this->db->exec($commands);

            $dbCartDetail->erase("accountId = '{$buyers[0]['accountId']}' AND entityProductId IN (" . implode(", ", $arrEntityProductId) . ") ");
        }

        // Send email to buyer
        $subTotalUSD = 0;
        $taxUSD = 0;
        $totalUSD = 0;
        if (count($arrCurrencyId) > 0) {
            $arrCurrency = $dbCurrencies->findWhere("id IN (" . implode(",", $arrCurrencyId) . ")");
            foreach ($arrCurrency as $currency) {
                $currencyId = $currency['id'];
                if (array_key_exists($currencyId, $mapCurrencyIdSubTotal)) {
                    $subTotal = $mapCurrencyIdSubTotal[$currencyId];
                    $subTotalUSD += $subTotal * $currency['conversionToUSD'];
                }

                if (array_key_exists($currencyId, $mapCurrencyIdTax)) {
                    $tax = $mapCurrencyIdTax[$currencyId];
                    $taxUSD += $tax * $currency['conversionToUSD'];
                }

                if (array_key_exists($currencyId, $mapCurrencyIdTotal)) {
                    $total = $mapCurrencyIdTotal[$currencyId];
                    $totalUSD += $total * $currency['conversionToUSD'];
                }
            }
        }

        $subTotal = $subTotalUSD / $buyers[0]['conversionToUSD'];
        $tax = $taxUSD / $buyers[0]['conversionToUSD'];
        $total = $totalUSD / $buyers[0]['conversionToUSD'];

        $this->f3->set('products', $arrProducts);
        $this->f3->set('currencySymbol', $buyers[0]['currencySymbol']);
        $this->f3->set('subTotal', round($subTotal, 2));
        $this->f3->set('tax', round($tax, 2));
        $this->f3->set('total', round($total, 2));
        $this->f3->set('ordersUrl', "web/pharmacy/order/history");

        $name = count($arrSellerName) > 1 ? "Seller names: " : "Seller name: ";
        $name .= implode(", ", $arrSellerName);
        $this->f3->set('name', $name);

        $this->f3->set('paymentMethod', null);

        $arrEntityUserProfile = $dbVwEntityUserProfile->getByField("entityId", $buyers[0]['entityId']);
        foreach ($arrEntityUserProfile as $entityUserProfile) {
            $emailHandler->appendToAddress($entityUserProfile->userEmail, $entityUserProfile->userFullName);
        }
        $htmlContent = View::instance()->render($emailFile);

        if (count($arrOrderId) > 1) {
            $subject = "Aumet - New Orders Confirmation (" . implode(", ", $arrOrderId) . ")";
        } else {
            $subject = "Aumet - New Order Confirmation (" . implode(", ", $arrOrderId) . ")";
        }
        if (getenv('ENV') != Constants::ENV_PROD) {
            $subject .= " - (Test: " . getenv('ENV') . ")";
            if (getenv('ENV') == Constants::ENV_LOCAL) {
                $emailHandler->resetTos();
                $emailHandler->appendToAddress("carl8smith94@gmail.com", "Antoine Abou Cherfane");
                $emailHandler->appendToAddress("patrick.younes.1.py@gmail.com", "Patrick");
                $emailHandler->appendToAddress("sajjadintel@gmail.com", "Sajad");
            }
        }
        $emailHandler->sendEmail(Constants::EMAIL_NEW_ORDER, $subject, $htmlContent);

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

        if ($dbOrder['statusId'] != Constants::ORDER_STATUS_PENDING)
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_order')), null);

        $entitySellerId = $dbOrder['entitySellerId'];

        // Check if products are valid
        $arrProducts = $this->requestData->products;
        $valid = is_array($arrProducts);

        // Check for missing id or quantity
        if ($valid) {
            $arrProductId = [];
            $mapProductIdQuantity = [];
            foreach ($arrProducts as $product) {
                if ((!isset($product->productId) || !$product->productId || !is_numeric($product->productId))
                    || (!isset($product->quantity) || !$product->quantity || !is_numeric($product->quantity))
                ) {
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
        if ($valid) {
            // Check if all ids are valid
            $dbProducts = new GenericModel($this->db, "vwEntityProductSell");
            $arrProductIdStr = implode(",", $arrProductId);
            $arrProductsDb = $dbProducts->findWhere("productId IN ($arrProductIdStr)");
            if (count($arrProductsDb) == count($arrProductId)) {
                foreach ($arrProductsDb as $productsDb) {
                    $entityProductId = $productsDb['id'];
                    $productId = $productsDb['productId'];
                    $entityId = $productsDb['entityId'];

                    // Check if quantity higher than availableQuantity or lower than minimumOrderQuantity or if product is from different distributor
                    $quantity = $mapProductIdQuantity[$productId];
                    $minimumOrderQuantity = $productsDb['minimumOrderQuantity'];
                    $availableQuantity = ProductHelper::getAvailableQuantity($productsDb['stock'], $productsDb['maximumOrderQuantity']);
                    if (
                        $quantity > $availableQuantity
                        || ($minimumOrderQuantity && $quantity < $minimumOrderQuantity)
                        || $entityId != $entitySellerId
                    ) {
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
        foreach ($arrOrderProducts as $orderProduct) {
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
            if (isset($this->requestData->note))
                $dbOrderDetail->note = $this->requestData->note;

            $dbOrderDetail->add();
        }

        // Update the order
        if (isset($this->requestData->note))
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
