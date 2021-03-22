<?php

namespace App\Resources\Interfaces;

interface Resource
{
    /**
     * Return an associative array ready to be converted to json
     *
     * @param \App\Model $data
     * @return array<string, any>
     */
    public static function format($data);

    /**
     * Return a collection of associative arrays ready to be converted to json
     *
     * @param \App\Model[] $data
     * @return array<array<string, any>>
     */
    public static function collection($data);
}