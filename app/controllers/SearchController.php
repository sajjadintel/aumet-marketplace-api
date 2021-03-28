<?php

class SearchController extends MainController
{

    public function getSellerList()
    {
        $limit = 10;
        if (isset($_GET['limit']))
            $limit = (int)$_GET['limit'];
        $order['limit'] = $limit;

        $offset = 0;
        if (isset($_GET['offset']))
            $offset = (int)$_GET['offset'];
        $order['offset'] = $offset;

        $search = null;
        if (isset($_GET['search']) && $_GET['search'] != "")
            $search = $_GET['search'];


        $filter = "typeId = 20 ";

        if ($search !== null) {
            $filter .= " AND ( name_en LIKE '%{$search}%'";
            $filter .= " OR name_fr LIKE '%{$search}%'";
            $filter .= " OR name_ar LIKE '%{$search}%' ) ";
        }

        $dbProducts = new GenericModel($this->db, "entity");
        $dbProducts->name = 'name_' . $this->language;

        $dataCount = $dbProducts->count($filter);
        $dbProducts->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $response['data'] = array_map(array($dbProducts, 'cast'), $dbProducts->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_seller')), $response);
    }
}
