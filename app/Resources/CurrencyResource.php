<?php

namespace App\Resources;

class CurrencyResource extends JsonResource
{
    public static function format($currency)
    {
        return [
            'id' => $currency->id,
            'name' => $currency->name,
            'short_name' => $currency->shortName,
            'symbol' => $currency->symbol,
            'conversion_to_usd' => $currency->conversionToUSD,
        ];
    }
}
