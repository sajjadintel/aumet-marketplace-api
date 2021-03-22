<?php

use App\Models\Entity;
use App\Models\Account;
use App\Resources\DistributorResource;

class DistributorsController extends MainController
{
    public function index()
    {
        $distributors = new Entity;
        $account = new Account;
        $account->load(['id = ?', $this->objUser->accountId]);
        $page = (int) ($this->f3->get('GET.page') ?? 1);
        $pageSize = (int) ($this->f3->get('GET.size') ?? 10);

        $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            DistributorResource::collection(
                $distributors->paginateDistributorsByCountry(
                    $page,
                    $pageSize,
                    $account->country()->id,
                )
            )
        );
    }
}