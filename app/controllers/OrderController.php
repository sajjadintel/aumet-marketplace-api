<?php

class OrderController extends MainController {

    public function getOrders()
    {
        $type = 'all';
        if ($this->f3->get('GET.type') && $this->f3->get('GET.type') != 'none') {
            $type = $this->f3->get('GET.type');
        }

        $limit = 10;
        if ($this->f3->get('GET.limit') && $this->f3->get('GET.limit') != 'none') {
            $limit = (int)$this->f3->get('GET.limit');
        }
        $order['limit'] = $limit;

        $offset = 0;
        if ($this->f3->get('GET.offset') && $this->f3->get('GET.offset') != 'none') {
            $offset = (int)$this->f3->get('GET.offset');
        }
        $order['offset'] = $offset;

        $sortBy = 'idDesc';
        if ($this->f3->get('GET.sort') && $this->f3->get('GET.sort') != 'none') {
            $sortBy = $this->f3->get('GET.sort');
        }
        $order['order'] = $sortBy;


        $arrEntityId = key($this->objEntityList);
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

        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_order')), $response);
    }

    public function postOrder()
    {

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
        $arrEntityId = key($this->objEntityList);
        $dbOrder = $dbOrder->getWhere("id = '$orderId' AND entityBuyerId IN ($arrEntityId)");

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