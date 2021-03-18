<?php

namespace App\Resources;

abstract class JsonResource implements Interfaces\Resource
{
    public abstract static function format($data);

    public static function collection($data)
    {
        $responseData = [];
        foreach ($data as $datum) {
            $responseData[] = static::format($datum);
        }

        return empty($responseData) ? null : $responseData;
    }
}