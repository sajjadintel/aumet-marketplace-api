<?php

class NewsController extends MainController {

    public function getNewsList()
    {
        $limit = 10;
        if (isset($_GET['limit']))
            $limit = (int)$_GET['limit'];
        $order['limit'] = $limit;

        $offset = 0;
        if (isset($_GET['offset']))
            $offset = (int)$_GET['offset'];
        $order['offset'] = $offset;

        $sortBy = 'id_desc';
        if (isset($_GET['sort']))
            $sortBy = $_GET['sort'];
        $order['order'] = $sortBy;


        $filter = "";

        switch ($sortBy) {
            case "rand":
                $orderString = "rand()";
                break;

            case "id_asc":
                $orderString = "id ASC";
                break;
            case "id_desc":
                $orderString = "id DESC";
                break;

            case "title_asc":
                $orderString = "title_en ASC, id ASC";
                break;
            case "title_desc":
                $orderString = "title_en DESC, id ASC";
                break;

            case "description_asc":
                $orderString = "description_en ASC, id ASC";
                break;
            case "description_desc":
                $orderString = "description_en DESC, id ASC";
                break;

            default:
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Sort')), null);
                return;
        }
        $order['order'] = $orderString;


        $dbNews = new GenericModel($this->db, "news");
        $dbNews->title = 'title_' . $this->language;
        $dbNews->description = 'description_' . $this->language;

        $dataCount = $dbNews->count($filter);
        $dbNews->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $response['data'] = array_map(array($dbNews, 'cast'), $dbNews->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_news')), $response);
    }

    public function getNews()
    {
        if (!$this->f3->get('PARAMS.id'))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_newsId')), null);

        $newsId = $this->f3->get('PARAMS.id');

        $dbNews = new GenericModel($this->db, "news");
        $dbNews->title = 'title_' . $this->language;
        $dbNews->description = 'description_' . $this->language;

        $response['data'] = $dbNews->findWhere("id = '$newsId' ")[0];

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_news')), $response);
    }

    public function getNewsTypeList()
    {
        $limit = 10;
        if (isset($_GET['limit']))
            $limit = (int)$_GET['limit'];
        $order['limit'] = $limit;

        $offset = 0;
        if (isset($_GET['offset']))
            $offset = (int)$_GET['offset'];
        $order['offset'] = $offset;

        $sortBy = 'id_desc';
        if (isset($_GET['sort']))
            $sortBy = $_GET['sort'];
        $order['order'] = $sortBy;


        $filter = "";

        switch ($sortBy) {
            case "rand":
                $orderString = "rand()";
                break;

            case "id_asc":
                $orderString = "id ASC";
                break;
            case "id_desc":
                $orderString = "id DESC";
                break;

            case "title_asc":
                $orderString = "title_en ASC, id ASC";
                break;
            case "title_desc":
                $orderString = "title_en DESC, id ASC";
                break;

            case "description_asc":
                $orderString = "description_en ASC, id ASC";
                break;
            case "description_desc":
                $orderString = "description_en DESC, id ASC";
                break;

            default:
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Sort')), null);
                return;
        }
        $order['order'] = $orderString;


        $dbNews = new GenericModel($this->db, "newsType");
        $dbNews->name = 'name_' . $this->language;

        $dataCount = $dbNews->count($filter);
        $dbNews->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $response['data'] = array_map(array($dbNews, 'cast'), $dbNews->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_newsType')), $response);
    }


}
