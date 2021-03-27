<?php

class GeoController extends MainController
{
    public function getRegisteredCountryList()
    {
        $dbCountry = new GenericModel($this->db, "country");
        $dbCountry->name = "name_en";
        $response['data'] = $dbCountry->findWhere("isRegisteredFrom = 1", "name ASC");
        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_country')), $response);
    }

    function getCityList()
    {
        $query = '';
        if (isset($_GET['countryId'])) {
            $countryId = $_GET['countryId'];
            $query = "countryId IN ($countryId)";
        }

        $dbCity = new GenericModel($this->db, "city");
        $dbCity->name = "name" . ucfirst($this->objUser->language);
        $response['data'] = $dbCity->findWhere($query, "countryId ASC, name ASC");

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_city')), $response);
    }
}
