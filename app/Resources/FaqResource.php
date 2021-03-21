<?php

namespace App\Resources;

class FaqResource extends JsonResource
{
    public static function format($faq)
    {
        $localizedNameField = 'name_' . \Base::instance()->get('locale');
        $localizedDescriptionField = 'description_' . \Base::instance()->get('locale');

        return [
            'id' => $faq->id,
            'name' => $faq->$localizedNameField,
            'description' => $faq->$localizedDescriptionField,
        ];
    }
}