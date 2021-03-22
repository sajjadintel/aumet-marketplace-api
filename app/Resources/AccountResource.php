<?php

namespace App\Resources;

class AccountResource extends JsonResource
{
    public static function format($account)
    {
        return [
            'id' => $account->id,
            'number' => $account->number,
            'entity' => EntityResource::format($account->entityId),
            'created_at' => $account->insertDateTime,
            'status_id' => $account->statusId, 
        ];
    }
}