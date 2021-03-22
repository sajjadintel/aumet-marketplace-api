<?php

namespace App\Resources;

class FaqResource extends JsonResource
{
    public static function format($faq)
    {
        return [
            'id' => $faq->id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'language' => $faq->language,
            'is_enabled' => $faq->isEnabled,
        ];
    }
}