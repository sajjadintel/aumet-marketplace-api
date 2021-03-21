<?php

use App\Models\Faq;
use App\Resources\FaqResource;

class FaqController extends MainController
{
    public function index()
    {
        $faq = new Faq;
        return $this->sendSuccess(
            $faq->response['statusCode'],
            $faq->response['message'],
            FaqResource::collection($faq->find())
        );
    }
}