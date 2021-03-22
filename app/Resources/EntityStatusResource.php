<?php

namespace App\Resources;

class EntityStatusResource extends JsonResource
{
    public static function format($status)
    {
        return empty($status) ? null : [
            'id' => $status->id,
            'name_en' => $status->name_en,
            'name_ar' => $status->name_ar,
            'name_fr' => $status->name_fr,
        ];
    }
}