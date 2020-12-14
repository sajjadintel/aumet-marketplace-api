<?php

class BookController extends MainController
{
    function beforeRoute()
    {
        $this->beforeRouteFunction();
    }

    public function getBookList()
    {
        if ($this->f3->get('PARAMS.limit') && $this->f3->get('PARAMS.limit') != 'none') {
            $limit = $this->f3->get('PARAMS.limit');
        }

        if ($this->f3->get('PARAMS.offset') && $this->f3->get('PARAMS.offset') != 'none') {
            $offset = $this->f3->get('PARAMS.offset');
        }

        $remainingParams = explode('/', $this->f3->get('PARAMS.*'));

        foreach ($remainingParams as $paramItem) {
            $param = explode('=', $paramItem);

            // Remaining wildcard parameters

            switch ($param[0]) {
                case 'sort';
                    $sortBy = $param[1];
                    break;
                case 'best_selling';
                    $best_selling = true;
                    break;
                case 'original_book';
                    $original_book = true;
                    break;
                case 'editor_pick';
                    $editor_pick = true;
                    break;
                case 'young_author';
                    $young_author = true;
                    break;
                case 'original_featured';
                    $original_featured = true;
                    break;
                case 'book_id';
                    $book_id = $param[1];
                    break;
                case 'author_id';
                    $author_id = $param[1];
                    break;
                case 'category_id';
                    $category_id = $param[1];
                    break;
                case 'subcategory_id';
                    $subcategory_id = $param[1];
                    break;
                case 'language_id';
                    $language_id = $param[1];
                    break;
                case 'tags_id';
                    $tags_id = '';
                    $tags = explode(',', $param[1]);
                    foreach ($tags as $tag) {
                        if ($tags_id != '') {
                            $tags_id .= '|';
                        }
                        $tags_id .= "^$tag^|,$tag^|^$tag,|,$tag,";
                    }
                    break;
                case 'price_min';
                    $price_min = $param[1];
                    break;
                case 'price_max';
                    $price_max = $param[1];
                    break;
                case 'not_book_id';
                    $not_book_id = $param[1];
                    break;
                default:
                    break;
            }
        }

        $filter = array('status = 1 AND hide != 1 ');
        $order = ['order' => 'id DESC'];

        if (isset($best_selling)) {
            $filter[0] .= ' AND best_selling = 1';
        }
        if (isset($original_book)) {
            $filter[0] .= ' AND original_book = 1';
        }
        if (isset($editor_pick)) {
            $filter[0] .= ' AND editor_pick = 1';
        }
        if (isset($original_featured)) {
            if ($this->language == 'ar') {
                $filter[0] .= ' AND original_book_featured_ar = 1';
            } else {
                $filter[0] .= ' AND original_book_featured = 1';
            }
        }
        if (isset($young_author)) {
            $filter[0] .= ' AND (DATEDIFF(CURDATE(), author_birth_date) / 365) <= 25';
        }
        if (isset($book_id)) {
            $filter[0] .= " AND id IN ($book_id)";
        }
        if (isset($author_id)) {
            $filter[0] .= " AND author_id IN ($author_id)";
        }
        if (isset($category_id)) {
            $filter[0] .= " AND category_id IN ($category_id)";
        }
        if (isset($subcategory_id)) {
            $filter[0] .= " AND subcategory_id IN ($subcategory_id)";
        }
        if (isset($tags_id)) {
            $filter[0] .= " AND book_tags_id REGEXP '$tags_id'";
        }
        if (isset($language_id)) {
            $filter[0] .= " AND language_id IN ($language_id)";
        }
        if (isset($not_book_id)) {
            $filter[0] .= " AND id NOT IN ($not_book_id)";
        }
        if (isset($price_min)) {
            $filter[0] .= " AND price >= $price_min";
        }
        if (isset($price_max)) {
            $filter[0] .= " AND price <= $price_max";
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
                case "popularDesc":
                    $orderString = "total_rating DESC, id ASC";
                    break;
                case "popularAsc":
                    $orderString = "total_rating ASC, id ASC";
                    break;
            }
            if ($orderString != '') {
                $order['order'] = $orderString;
            }
        }

        $genericModel = new GenericModel($this->db, "vw_books_categories_author_tags_withicondownloads");
        $dataCount = $genericModel->count($filter);
        $genericModel->reset();

        $genericModel->minPrice = 'MIN(price)';
        $genericModel->maxPrice = 'MAX(price)';
        $genericModel->load($filter);

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;
        $dataFilter->minPrice = $genericModel->minPrice;
        $dataFilter->maxPrice = $genericModel->maxPrice;
        $genericModel = new GenericModel($this->db, "vw_books_categories_author_tags_withicondownloads");

        $response['dataFilter'] = $dataFilter;
        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find($filter, $order));


        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_books')), $response);
    }

    public function getBookDetails()
    {
        $id = (int)$this->f3->get('PARAMS.id');
        $genericModel = new GenericModel($this->db, "vw_books_categories_author_tags_withicondownloads");
        $book = array_map(array($genericModel, 'cast'), $genericModel->find(array('id = ?', $id)));

        if (empty($book)) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_book')), null);
        }

        $genericModel = new GenericModel($this->db, "vw_book_tags");
        $tags = array_map(array($genericModel, 'cast'), $genericModel->find(array('book_id = ?', $id)));

        $response['book'] = $book;
        $response['tags'] = $tags;

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_book')), $response);
    }

    public function getBookCategories()
    {
        $genericModel = new GenericModel($this->db, "categories");
        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find());

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_bookCategories')), $response);
    }

    public function getBookSubcategories()
    {
        $genericModel = new GenericModel($this->db, "vw_categories_subcategories");
        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find());

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_bookSubcategories')), $response);
    }

    public function getBookSubcategoriesOfCategory()
    {
        $id = (int)$this->f3->get('PARAMS.id');
        $genericModel = new GenericModel($this->db, "vw_categories_subcategories");
        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find(array('category_id = ?', $id)));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_bookSubcategory')), $response);
    }

    public function postUpdateBookViews()
    {
        $id = $this->requestData->id ? (int)$this->requestData->id :
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_bookId')), null);

        $book = new GenericModel($this->db, "books");
        $book->load(array('id=?', $id));

        if ($book->dry()) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_book')), null);
        }
        $book->views++;

        if (!$book->edit()) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $book->exception, null);
        }

        $author = new GenericModel($this->db, "author");
        $author->load(array('id=?', $book->author_id));

        if ($author->dry()) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_poet')), null);
        }
        $author->views++;

        if (!$author->edit()) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $author->exception, null);
        }

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_updated', $this->f3->get('RESPONSE.entity_bookViews')), null);
    }

    public function getBookReviews()
    {
        $id = (int)$this->f3->get('PARAMS.id');
        $genericModel = new GenericModel($this->db, "book_rating");

        $response['dataFilter']['dataCount'] = $genericModel->count(array('book_id = ? AND verified = 1', $id));
        $genericModel->reset();

        $response['data'] = array_map(array($genericModel, 'cast'), $genericModel->find(array('book_id = ? AND verified = 1', $id)));

        $genericModel->reset();
        $genericModel->user_count = 'SUM(verified)';

        $order = ['order' => 'review_rating', 'group' => 'review_rating'];
        $response['dataStatistics'] = array_map(array($genericModel, 'cast'), $genericModel->find(array('book_id = ? AND verified = 1', $id), $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_bookReviews')), $response);
    }

    public function postAddReview()
    {
        $this->validateUser();

        $bookId = $this->requestData->bookId ? $this->requestData->bookId :
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_bookId')), null);
        $displayName = $this->requestData->displayName ? $this->requestData->displayName :
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_displayName')), null);
        $reviewTitle = $this->requestData->reviewTitle ? $this->requestData->reviewTitle :
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_reviewTitle')), null);
        $reviewMessage = $this->requestData->reviewMessage ? $this->requestData->reviewMessage :
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_reviewMessage')), null);
        $reviewRating = $this->requestData->reviewRating ? $this->requestData->reviewRating :
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_reviewRating')), null);

        $genericModel = new GenericModel($this->db, "book_rating");
        $genericModel->load(array('book_id = ? AND user_id = ?', $bookId, $this->objUser->id));

        if (!$genericModel->dry()) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.403_reviewExists'), null);
        }

        $genericModel->book_id = $bookId;
        $genericModel->user_id = $this->objUser->id;
        $genericModel->display_name = $displayName;
        $genericModel->review_rating = $reviewRating;
        $genericModel->review_title = $reviewTitle;
        $genericModel->review_message = $reviewMessage;
        $genericModel->verified = 0;
        $genericModel->date = date("Y-m-d");

        if (!$genericModel->add()) {
            $this->sendError(Constants::HTTP_FORBIDDEN, $genericModel->exception, null);
        }

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_bookReview')), null);
    }
}
