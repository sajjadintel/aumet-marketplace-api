<?php

class DisplayController extends MainController
{
    public function getTextPage()
    {
        $id = (int)$this->f3->get('PARAMS.id');

        $genericModel = new GenericModel($this->db, "pages");
        $response = array_map(array($genericModel, 'cast'), $genericModel->find(array('id=?', $id)));

        if (empty($response)) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_page')), null);
        }

        $this->sendSuccess(Constants::HTTP_OK,  $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_page')), $response);
    }

    public function getTextIconTestimonials()
    {
        $typeId = (int)$this->f3->get('PARAMS.typeId');

        $genericModel = new GenericModel($this->db, "vw_testimonials_withicon");
        $response = array_map(array($genericModel, 'cast'), $genericModel->find(array('type_id=?', $typeId), ['limit' => '12']));

        if (empty($response)) {
            $this->sendError(Constants::HTTP_UNAUTHORIZED, $this->f3->get('RESPONSE.404_listNotFound', $this->f3->get('RESPONSE.entity_testimonials')), null);
        }

        $this->sendSuccess(Constants::HTTP_OK,  $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_testimonials')), $response);
    }

    public function getTextContentTags()
    {
        $genericModel = new GenericModel($this->db, "tags");
        $arrData = array_map(array($genericModel, 'cast'), $genericModel->find(null, array('order' => 'title')));
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_tags')), $arrData);
    }

    public function getTextContentLanguages()
    {
        $genericModel = new GenericModel($this->db, "languages");
        $arrData = array_map(array($genericModel, 'cast'), $genericModel->find());
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_languages')), $arrData);
    }

    public function getTextFaq()
    {
        $genericModel = new GenericModel($this->db, "faq");
        $arrData = array_map(array($genericModel, 'cast'), $genericModel->find(null, ['order' => 'order_faq ASC, id DESC']));
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_faq')), $arrData);
    }

    public function getTextIconSetApart()
    {
        $genericModel = new GenericModel($this->db, "vw_set_apart_withicon");
        $arrData = array_map(array($genericModel, 'cast'), $genericModel->find(null, ['order' => 'id DESC']));
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_setApart')), $arrData);
    }

    public function getTextIconHomePromo()
    {
        $genericModel = new GenericModel($this->db, "vw_promo_withicon");
        $arrData = array_map(array($genericModel, 'cast'), $genericModel->find(null, ['order' => 'id DESC']));
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_homePromo')), $arrData);
    }

    public function getTextIconWhyChooseUs()
    {
        $genericModel = new GenericModel($this->db, "vw_why_choose_us_withicon");
        $arrData = array_map(array($genericModel, 'cast'), $genericModel->find(null, ['limit' => '10']));
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_whyChooseUs')), $arrData);
    }

    public function getTextIconMakeMoneyWithUs()
    {
        $genericModel = new GenericModel($this->db, "vw_make_money_categories_withicon");
        $arrData = array_map(array($genericModel, 'cast'), $genericModel->find(null, ['limit' => '2']));
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_makeMoneyCategories')), $arrData);
    }
}
