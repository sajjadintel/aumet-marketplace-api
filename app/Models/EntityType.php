<?php

namespace App\Models;

class EntityType extends Model
{
    const TYPE_PHARMACY_CHAIN = 21;
    const TYPE_PHARMACY = 20;
    const TYPE_SUB_DISTRIBUTOR = 11;
    const TYPE_DISTRIBUTOR = 10;
    protected $table = 'entityType';
}