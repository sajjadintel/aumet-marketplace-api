<?php

namespace App\Resources;

abstract class JsonResource implements Interfaces\Resource
{
    public abstract static function format($model);

    public static function collection($models)
    {
        if (!$models instanceof \DB\CortexCollection) {
            return [];
        }
        
        $responseData = [];
        foreach ($models as $model) {
            $responseData[] = static::format($model);
        }

        return $responseData;
    }
}