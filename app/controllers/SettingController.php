<?php

class SettingController extends MainController {

    public function getSetting()
    {
        $dbSetting = new GenericModel($this->db, "setting");
        $settings = $dbSetting->findAll();

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_detailFound', $this->f3->get('RESPONSE.entity_setting')), $settings);
    }

}
