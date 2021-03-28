<?php

namespace App\Resources;

class CurrencyResource extends JsonResource
{
    public static function format($currency)
    {
        return [
            'id' => $currency->id,
            'name' => $currency->name,
            'shortName' => $currency->shortName,
            'symbol' => $currency->symbol,
            'conversionToUSD' => $currency->conversionToUSD,
        ];
    }
}
