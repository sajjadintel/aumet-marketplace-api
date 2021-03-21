<?php

use App\Models\Faq;
use App\Resources\FaqResource;

class FaqController extends MainController
{
    public function index()
    {
        $faq = new Faq;
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            FaqResource::collection($faq->find(['language = ? AND isEnabled = ?', $this->objUser->language, 1]) ?: [])
        );
    }
}