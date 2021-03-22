<?php

namespace App\Models;

use DB\SQL\Schema;

class Entity extends Model
{
    protected $table = 'entity';
    protected $fieldConf = [
        'accounts' => [
            'has-many' => [Account::class, 'entityId']
        ],
        'countryId' => [
            'belongs-to-one' => Country::class,
        ],
        'currencyId' => [
            'belongs-to-one' => Currency::class,
        ],
        'statusId' => [
            'belongs-to-one' => EntityStatus::class
        ],
        'entityBranches' => [
            'has-many' => [EntityBranch::class, 'entityId'],
        ],
        'id' => [
            'type' => Schema::DT_INT,
        ],
        'name_en' => [
            'type' => Schema::DT_VARCHAR128,
        ],
        'name_ar' => [
            'type' => Schema::DT_VARCHAR128,
        ],
        'name_fr' => [
            'type' => Schema::DT_VARCHAR128,
        ],
        'image' => [
            'type' => Schema::DT_VARCHAR512,
        ],
        'insertDateTime' => [
            'type' => Schema::DT_DATETIME,
        ],
    ];

    public function paginateDistributors($page = 1, $pageSize = 10)
    {
        return $this->paginate(
            $page - 1,
            $pageSize,
            ['typeId = ?', EntityType::TYPE_DISTRIBUTOR]
        );
    }

    public function paginateDistributorsByCountry($page = 1, $pageSize = 10, $countryId)
    {
        $distributors = $this->paginate(
            $page - 1,
            $pageSize,
            ['typeId = ? AND countryId = ?', EntityType::TYPE_DISTRIBUTOR, $countryId]
        );

        return isset($distributors['subset'])
                ? $distributors['subset']
                : [];
    }
}
