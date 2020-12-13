<?php


class ProductController extends MainController {

    public function getProducts()
    {
        $limit = 10;
        if ($_GET['limit']) {
            $limit = (int)$_GET['limit'];
        }
        $order['limit'] = $limit;

        $offset = 0;
        if ($_GET['offset']) {
            $offset = (int)$_GET['offset'];
        }
        $order['offset'] = $offset;

        $sortBy = 'idDesc';
        if ($_GET['sort']) {
            $sortBy = $_GET['sort'];
        }
        $order['order'] = $sortBy;


        $filter = "";


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

            case "productNameAsc":
                $orderString = "productName_en ASC, id ASC";
                break;
            case "productNameDesc":
                $orderString = "productName_en DESC, id ASC";
                break;

            case "scientificNameAsc":
                $orderString = "scientificName ASC, id ASC";
                break;
            case "scientificNameDesc":
                $orderString = "scientificName DESC, id ASC";
                break;

            case "unitPriceAsc":
                $orderString = "unitPrice ASC, id ASC";
                break;
            case "unitPriceDesc":
                $orderString = "unitPrice DESC, id ASC";
                break;

            case "vatAsc":
                $orderString = "vat ASC, id ASC";
                break;
            case "vatDesc":
                $orderString = "vat DESC, id ASC";
                break;

            case "stockStatusNameAsc":
                $orderString = "stockStatusName_en ASC, id ASC";
                break;
            case "stockStatusNameDesc":
                $orderString = "stockStatusName_en DESC, id ASC";
                break;

            case "stockAsc":
                $orderString = "stock ASC, id ASC";
                break;
            case "stockDesc":
                $orderString = "stock DESC, id ASC";
                break;

            case "stockUpdatedAsc":
                $orderString = "stockUpdateDateTime ASC, id ASC";
                break;
            case "stockUpdatedDesc":
                $orderString = "stockUpdateDateTime DESC, id ASC";
                break;

            case "madeInCountryNameAsc":
                $orderString = "madeInCountryName_en ASC, id ASC";
                break;
            case "madeInCountryNameDesc":
                $orderString = "madeInCountryName_en DESC, id ASC";
                break;

            default:
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Sort')), null);
                return;
        }
        $order['order'] = $orderString;


        $genericModel = new GenericModel($this->db, "vwEntityProductSell");
        $dataCount = $genericModel->count($filter);
        $genericModel->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_product')), $response);
    }

    public function getProduct()
    {
        if (!$this->f3->get('PARAMS.id')) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_orderId')), null);
        }
        $productId = $this->f3->get('PARAMS.id');


        $order = new GenericModel($this->db, "vwEntityProductSell");
        $response['data'] = $order->findWhere("id = '$productId' ")[0];

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_product')), $response);
    }

}