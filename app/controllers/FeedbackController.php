<?php


class FeedbackController extends MainController {


    public function getFeedbacksHistory()
    {
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
            case "addedAsc":
                $orderString = "createdAt ASC, id ASC";
                break;
            case "addedDesc":
                $orderString = "createdAt DESC, id ASC";
                break;
            case "starsAsc":
                $orderString = "stars ASC, id ASC";
                break;
            case "starsDesc":
                $orderString = "stars DESC, id ASC";
                break;
            default:
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Sort')), null);
                return;
        }
        $order['order'] = $orderString;


        $genericModel = new GenericModel($this->db, "vwOrderUserRate");
        $dataCount = $genericModel->count($filter);
        $genericModel->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_feedback')), $response);
    }


    public function getFeedbacksPending()
    {
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
        $filter .= " AND statusId IN (6,7)";


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
            case "addedAsc":
                $orderString = "createdAt ASC, id ASC";
                break;
            case "addedDesc":
                $orderString = "createdAt DESC, id ASC";
                break;
            case "starsAsc":
                $orderString = "stars ASC, id ASC";
                break;
            case "starsDesc":
                $orderString = "stars DESC, id ASC";
                break;
            default:
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Sort')), null);
                return;
        }
        $order['order'] = $orderString;


        $genericModel = new GenericModel($this->db, "vwOrderEntityUserFeedbackPending");
        $dataCount = $genericModel->count($filter);
        $genericModel->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_feedback')), $response);
    }


    public function postFeedback()
    {
        if (!$this->f3->get('POST.id'))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_orderId')), null);
        if (!$this->f3->get('POST.rating'))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_feedbackRating')), null);
        if (!$this->f3->get('POST.comment'))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_feedbackMessage')), null);

        $orderId = $this->f3->get('POST.id');
        $rating = $this->f3->get('POST.rating');
        $comment = $this->f3->get('POST.comment');
        $userId = $this->objUser->id;


        $order = new GenericModel($this->db, "vwOrderEntityUser");
        $arrEntityId = key($this->objEntityList);
        $order = $order->findWhere("id = '$orderId' entityBuyerId IN ($arrEntityId)");

        if ($order == null) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.403_permissionDenied', $this->f3->get('RESPONSE.entity_feedback')), null);
        }


        $orderRating = new GenericModel($this->db, "orderRating");
        $orderRating = $orderRating->findWhere("orderId = '$orderId' AND userId= '$userId'");

        if ($orderRating != null) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.403_alreadyExists', $this->f3->get('RESPONSE.entity_feedback')), null);
            return;
        }

        $dbProduct = new BaseModel($this->db, "orderRating");
        $dbProduct->orderId = $orderId;
        $dbProduct->userId = $userId;
        $dbProduct->rateId = $rating;
        $dbProduct->feedback = $comment;

        $dbProduct->add();


        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_feedback')), null);
    }


}