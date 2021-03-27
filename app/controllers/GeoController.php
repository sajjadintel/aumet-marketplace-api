<?php

class GeoController extends MainController
{
    public function getCountryList()
    {
        $query = '1 = 1';

        if (isset($_GET['isRegistered']) && $_GET['isRegistered'] == 1) {
            $query .= ' AND isRegisteredFrom = 1 ';
        }

        if (isset($_GET['countryId'])) {
            $countryId = $_GET['countryId'];
            $query .= " AND countryId IN ($countryId)";
        }

        $dbCountry = new GenericModel($this->db, "country");
        $dbCountry->name = "name_en";
        $dbCountry->getWhere($query, "name ASC");

        $data = array();
        while (!$dbCountry->dry()) {
            array_push($data, array("id" => $dbCountry->id, "name" => $dbCountry->name, "currency" => $dbCountry->currency, "countryCode", $dbCountry->countryCode, "flag" => $this->f3->get('API_URL') . "assets/img/countries/" . strtolower(Helper::getCountryIso($dbCountry->name_en)) . ".svg"));
            $dbCountry->next();
        }
        $response['data'] = $data;

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
