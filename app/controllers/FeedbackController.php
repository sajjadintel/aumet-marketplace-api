<?php


class FeedbackController extends MainController {


    public function getFeedbacks()
    {
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

        $genericModel->rateName = "rateName_" . $this->language;
        $genericModel->entityName = "entityName_" . $this->language;

        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_feedback')), $response);
    }


    public function postFeedback()
    {
        if (!$this->requestData->id)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_orderId')), null);
        if (!$this->requestData->rating)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_feedbackRating')), null);
        if (!$this->requestData->comment)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_feedbackMessage')), null);

        $orderId = $this->requestData->id;
        $rating = $this->requestData->rating;
        $comment = $this->requestData->comment;
        $userId = $this->objUser->id;


        $order = new GenericModel($this->db, "vwOrderEntityUser");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $order = $order->findWhere("id = '$orderId' AND entityBuyerId IN ($arrEntityId)");

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

        $order->feedbackSubmitted = 1;
        $order->edit();

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_feedback')), null);
    }


}