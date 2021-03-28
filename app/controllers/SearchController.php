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

        $sortBy = 'id_desc';
        if (isset($_GET['sort']))
            $sortBy = $_GET['sort'];
        $order['order'] = $sortBy;

        $search = null;
        if (isset($_GET['search']) && $_GET['search'] != "")
            $search = $_GET['search'];


        $filter = "typeId = 20 AND countryId IN (" . implode(",", $this->objEntityCountryList) . ")";

        if ($search !== null) {
            $filter .= " AND ( name_en LIKE '%{$search}%'";
            $filter .= " OR name_fr LIKE '%{$search}%'";
            $filter .= " OR name_ar LIKE '%{$search}%' ) ";
        }

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

            case "name_asc":
                $orderString = "name_en ASC, id ASC";
                break;
            case "name_desc":
                $orderString = "name_en DESC, id ASC";
                break;

            default:
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Sort')), null);
                return;
        }

        $order['order'] = $orderString;

        $dbEntities = new GenericModel($this->db, "vwEntityWithProducts");
        $dbEntities->name = 'name_' . $this->language;

        $dataCount = $dbEntities->count($filter);
        $dbEntities->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $response['data'] = array_map(array($dbEntities, 'cast'), $dbEntities->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_seller')), $response);
    }
}
