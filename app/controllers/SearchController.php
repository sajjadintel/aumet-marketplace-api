<?php

class SearchController extends MainController
{
    function beforeRoute()
    {
        $this->beforeRouteFunction();
    }

    public function getSearchResults()
    {
        $type = 'all';
        if ($this->f3->get('PARAMS.type')) {
            $type = $this->f3->get('PARAMS.type');
        }

        $order = ['order' => 'id DESC'];
        $searchItem = $this->f3->get('PARAMS.search');
        $searchLike = '%' . $searchItem . '%';

        if ($this->f3->get('PARAMS.limit') && $this->f3->get('PARAMS.limit') != 'none') {
            $limit = $this->f3->get('PARAMS.limit');
        }

        if ($this->f3->get('PARAMS.offset') && $this->f3->get('PARAMS.offset') != 'none') {
            $offset = $this->f3->get('PARAMS.offset');
        }

        if ($this->f3->get('PARAMS.sort') && $this->f3->get('PARAMS.sort') != 'none') {
            $sortBy = $this->f3->get('PARAMS.sort');
        }

        if (isset($limit)) {
            $order['limit'] = $limit;
        }
        if (isset($offset)) {
            $order['offset'] = $offset;
        }


        $dataFilter = new stdClass();
        $dataFilter->type = $type;
        $dataFilter->search = $searchItem;
        $dataFilter->dataCount = 0;

        $arrDataBook = [];
        $arrDataAuthor = [];
        $arrDataFilterBook = [];
        $arrDataFilterAuthor = [];

        if ($type == 'all' || $type == 'poet') {
            $filter = array("title LIKE ? OR title_ar LIKE ? OR tagline LIKE ? OR tagline_ar LIKE ?", $searchLike, $searchLike, $searchLike, $searchLike);

            if (isset($sortBy)) {
                $orderString = '';
                switch ($sortBy) {
                    case "rand":
                        $orderString = "rand()";
                        break;
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
                }
                if ($orderString != '') {
                    $order['order'] = $orderString;
                }
            }

            $genericModel = new GenericModel($this->db, "vw_poet_withnationality_withicon");
            $dataCount = $genericModel->count($filter);
            $genericModel->reset();

            $dataFilterAuthor = new stdClass();
            $dataFilter->dataCount += $dataCount;
            $dataFilterAuthor->dataCount = $dataCount;
            $dataFilterAuthor->filter = $filter;
            $dataFilterAuthor->order = $order;
            $arrDataFilterAuthor = $dataFilterAuthor;
            $genericModel->reset();

            $arrDataAuthor = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));
        }

        if ($type == 'all' || $type == 'book') {
            $filter = array("title LIKE ? OR category LIKE ? OR category_ar LIKE ? OR subcategory LIKE ? OR subcategory_ar LIKE ? OR author LIKE ? OR author_ar LIKE ?", $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike);



            if (isset($sortBy)) {
                $orderString = '';
                switch ($sortBy) {
                    case "rand":
                        $orderString = "rand()";
                        break;
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
                        $orderString = "date_posted DESC, id ASC";
                        break;
                    case "addedDesc":
                        $orderString = "date_posted ASC, id ASC";
                        break;
                    case "salesDesc":
                        $orderString = "total_orders DESC, id ASC";
                        break;
                    case "salesAsc":
                        $orderString = "total_orders ASC, id ASC";
                        break;
                }
                if ($orderString != '') {
                    $order['order'] = $orderString;
                }
            }

            $genericModel = new GenericModel($this->db, "vw_books_categories_author_tags_withicondownloads");
            $dataCount = $genericModel->count($filter);
            $genericModel->reset();

            $dataFilterBook = new stdClass();
            $dataFilter->dataCount += $dataCount;
            $dataFilterBook->dataCount = $dataCount;
            $dataFilterBook->filter = $filter;
            $dataFilterBook->order = $order;
            $arrDataFilterBook = $dataFilterBook;
            $genericModel->reset();

            $arrDataBook = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));
        }

        $response['dataFilterAuthor'] = $arrDataFilterAuthor;
        $response['dataFilterBook'] = $arrDataFilterBook;
        $response['dataFilter'] = $dataFilter;
        $response['dataAuthor'] = $arrDataAuthor;
        $response['dataBook'] = $arrDataBook;

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_searchQuery')), $response);
    }
}
