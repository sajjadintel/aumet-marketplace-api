<?php


class ProductController extends MainController {

    public function getProducts()
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

        $queryParam = null;
        if (isset($_GET['search']))
            $queryParam = $_GET['search'];

        $bonusType = null;
        if (isset($_GET['bonus_type']))
            $bonusType = $_GET['bonus_type'];

        $stockStatus = null;
        if (isset($_GET['stock_status']) && is_numeric($_GET['stock_status']))
            $stockStatus = $_GET['stock_status'];


        $sellerId = null;
        if (isset($_GET['seller_id']) && preg_match('/^[0-9,]+$/', $_GET['seller_id']))
            $sellerId = $_GET['seller_id'];

        $categoryId = null;
        if (isset($_GET['category_id']) && preg_match('/^[0-9,]+$/', $_GET['category_id']))
            $categoryId = $_GET['category_id'];

        $subcategoryId = null;
        if (isset($_GET['subcategory_id']) && preg_match('/^[0-9,]+$/', $_GET['subcategory_id']))
            $subcategoryId = $_GET['subcategory_id'];

        $countryId = null;
        if (isset($_GET['country_id']) && preg_match('/^[0-9,]+$/', $_GET['country_id']))
            $countryId = $_GET['country_id'];


        $filter = "1=1 ";

        if ($queryParam !== null) {
            $filter .= " AND ( scientificName LIKE '%{$queryParam}%'";
            $filter .= " OR productName_ar LIKE '%{$queryParam}%'";
            $filter .= " OR productName_en LIKE '%{$queryParam}%'";
            $filter .= " OR productName_fr LIKE '%{$queryParam}%' ) ";
        }

        if ($bonusType !== null) {
            if ($bonusType == 1) {
                $filter .= " AND bonusConfig IS NULL";
            } else if ($bonusType == 2) {
                $filter .= " AND bonusConfig IS NOT NULL";
            }
        }

        if ($stockStatus !== null) {
            $filter .= " AND stockStatusId = $stockStatus";
        }

        if ($sellerId !== null) {
            $filter .= " AND entityId IN ($sellerId)";
        }

        if ($categoryId !== null) {
            $filter .= " AND categoryId IN ($categoryId)";
        }

        if ($subcategoryId !== null) {
            $filter .= " AND subCategoryId IN ($subcategoryId)";
        }

        if ($countryId !== null) {
            $filter .= " AND madeInCountryId IN ($subcategoryId)";
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

            case "product_name_asc":
                $orderString = "productName_en ASC, id ASC";
                break;
            case "product_name_desc":
                $orderString = "productName_en DESC, id ASC";
                break;

            case "scientific_name_asc":
                $orderString = "scientificName ASC, id ASC";
                break;
            case "scientific_name_desc":
                $orderString = "scientificName DESC, id ASC";
                break;

            case "unit_price_asc":
                $orderString = "unitPrice ASC, id ASC";
                break;
            case "unit_price_desc":
                $orderString = "unitPrice DESC, id ASC";
                break;

            case "vat_asc":
                $orderString = "vat ASC, id ASC";
                break;
            case "vat_desc":
                $orderString = "vat DESC, id ASC";
                break;

            case "stock_status_name_asc":
                $orderString = "stockStatusName_en ASC, id ASC";
                break;
            case "stock_status_name_desc":
                $orderString = "stockStatusName_en DESC, id ASC";
                break;

            case "stock_asc":
                $orderString = "stock ASC, id ASC";
                break;
            case "stock_desc":
                $orderString = "stock DESC, id ASC";
                break;

            case "stock_updated_asc":
                $orderString = "stockUpdateDateTime ASC, id ASC";
                break;
            case "stock_updated_desc":
                $orderString = "stockUpdateDateTime DESC, id ASC";
                break;

            case "made_in_country_name_asc":
                $orderString = "madeInCountryName_en ASC, id ASC";
                break;
            case "made_in_country_name_desc":
                $orderString = "madeInCountryName_en DESC, id ASC";
                break;

            default:
                $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Sort')), null);
                return;
        }
        $order['order'] = $orderString;


        $dbProducts = new GenericModel($this->db, "vwEntityProductSell");
        $dbProducts->productName = "productName_" . $this->language;
        $dbProducts->entityName = "entityName_" . $this->language;
        $dbProducts->bonusTypeName = "bonusTypeName_" . $this->language;
        $dbProducts->madeInCountryName = "madeInCountryName_" . $this->language;

        $dataCount = $dbProducts->count($filter);
        $dbProducts->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;

        $response['data'] = array_map(array($dbProducts, 'cast'), $dbProducts->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_product')), $response);
    }

    public function getProduct()
    {
        if (!$this->f3->get('PARAMS.id'))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_productId')), null);

        $productId = $this->f3->get('PARAMS.id');


        $dbProduct = new GenericModel($this->db, "vwEntityProductSell");
        $dbProduct->productName = "productName_" . $this->language;
        $dbProduct->entityName = "entityName_" . $this->language;
        $dbProduct->bonusTypeName = "bonusTypeName_" . $this->language;
        $dbProduct->madeInCountryName = "madeInCountryName_" . $this->language;

        $response['data'] = $dbProduct->findWhere("id = '$productId' ")[0];

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_product')), $response);
    }

    public function getProductBonus()
    {
        if (!$this->f3->get('PARAMS.productId'))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_productId')), null);

        $productId = $this->f3->get('PARAMS.productId');

        $dbProduct = new GenericModel($this->db, "vwEntityProductSell");
        $dbProduct->productName = "productName_" . $this->language;
        $dbProduct->entityName = "entityName_" . $this->language;
        $dbProduct->bonusTypeName = "bonusTypeName_" . $this->language;
        $dbProduct->madeInCountryName = "madeInCountryName_" . $this->language;

        $arrProduct = $dbProduct->findWhere("productId = '$productId'");

        $dbBonus = new GenericModel($this->db, "entityProductSellBonusDetail");
        $dbBonus->bonusId = 'id';
        $arrBonus = $dbBonus->findWhere("entityProductId = '$productId' AND isActive = 1");

        $data['product'] = $arrProduct[0];
        $data['bonus'] = $arrBonus;

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_bonus')), $data);
    }
}
