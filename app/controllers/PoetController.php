<?php

class PoetController extends MainController
{
    function beforeRoute()
    {
        $this->beforeRouteFunction();
    }

    public function getPoetList()
    {
        if ($this->f3->get('PARAMS.limit') && $this->f3->get('PARAMS.limit') != 'none') {
            $limit = $this->f3->get('PARAMS.limit');
        }

        if ($this->f3->get('PARAMS.offset') && $this->f3->get('PARAMS.offset') != 'none') {
            $offset = $this->f3->get('PARAMS.offset');
        }

        if ($this->f3->get('PARAMS.sortBy') && $this->f3->get('PARAMS.sortBy') != 'none') {
            $sortBy = $this->f3->get('PARAMS.sortBy');
        }

        if ($this->f3->get('PARAMS.nationality') && $this->f3->get('PARAMS.nationality') != 'all') {
            $nationality = $this->f3->get('PARAMS.nationality');
        }

        if ($this->f3->get('PARAMS.featured') && $this->f3->get('PARAMS.featured') != 'all') {
            $featured = $this->f3->get('PARAMS.featured');
        }

        $filter = null;
        $order = ['order' => 'id DESC'];

        if (isset($nationality)) {
            $filter = array('en_short_name=?', $nationality);
        }

        if (isset($featured)) {
            if ($filter != '') {
                $filter[0] .= ' AND author_featured = 1';
            } else {
                $filter = array('author_featured=1');
            }
        }
        if (isset($limit)) {
            $order['limit'] = $limit;
        }
        if (isset($offset)) {
            $order['offset'] = $offset;
        }

        if (isset($sortBy)) {
            $orderString = '';
            switch ($sortBy) {
                case "alphabeticalAsc":
                    $orderString = "title ASC, id ASC";
                    break;
                case "alphabeticalDesc":
                    $orderString = "title DESC, id ASC";
                    break;
                case "popularityAsc":
                    $orderString = "views DESC, id ASC";
                    break;
                case "popularityDesc":
                    $orderString = "views ASC, id ASC";
                    break;
                case "addedAsc":
                    $orderString = "created_at DESC, id ASC";
                    break;
                case "addedDesc":
                    $orderString = "created_at ASC, id ASC";
                    break;
                case "salesDesc":
                    $orderString = "total_orders DESC, id ASC";
                    break;
                case "salesAsc":
                    $orderString = "total_orders ASC, id ASC";
                    break;
                case "awardsDesc":
                    $orderString = "award DESC, id ASC";
                    break;
                case "awardsAsc":
                    $orderString = "award ASC, id ASC";
                    break;
            }
            if ($orderString != '') {
                $order['order'] = $orderString;
            }
        }

        $genericModel = new GenericModel($this->db, "vw_poet_withnationality_withicon");
        $dataCount = $genericModel->count($filter);
        $genericModel->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_poets')), $response);
    }

    public function getPoetDetails()
    {
        $id = (int)$this->f3->get('PARAMS.id');
        $genericModel = new GenericModel($this->db, "vw_poet_withnationality_withicon");
        $poet = array_map(array($genericModel, 'cast'), $genericModel->find(array('id = ?', $id)));

        if (empty($poet)) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_poet')), null);
        }

        $genericModel = new GenericModel($this->db, "awards");
        $awards = array_map(array($genericModel, 'cast'), $genericModel->find(array('author_id = ?', $id), ['order' => 'date DESC']));

        $genericModel = new GenericModel($this->db, "bibliography");
        $bibliography = array_map(array($genericModel, 'cast'), $genericModel->find(array('author_id = ?', $id), ['order' => 'date DESC']));

        $response['poet'] = $poet;
        $response['awards'] = $awards;
        $response['bibliography'] = $bibliography;

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_poet')), $response);
    }

    public function getPoetNationalityList()
    {
        $genericModel = new GenericModel($this->db, "vw_poet_nationalities");
        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find());

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_poetNationality')), $response);
    }
}
