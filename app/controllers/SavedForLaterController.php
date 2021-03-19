<?php

use App\Models\CartDetail;
use App\Models\SavedForLater;
use App\Models\User;
use App\Resources\SavedForLaterResource;
use App\Resources\CartDetailResource;

class SavedForLaterController extends MainController
{
    protected $accountId;
    function beforeRoute()
    {
        $this->objUser = new stdClass;
        $this->objUser->id = 12;
        $user = new User;
        $user->id = $this->objUser->id;
        $this->accountId = $user->accounts[0]->id;
        $this->parseJson();
        #parent::beforeRoute();
    }
    public function index()
    {
        $user = new User;
        $user->id = $this->objUser->id;
        
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            SavedForLaterResource::collection(
                $user->savedForLater()
            )
        );
    }

    public function create()
    {
        $data = $this->f3->get('JSON');
        $data = array_merge($data, ['account_id' => $this->accountId]);
        $savedForLater = new SavedForLater;
        $savedForLater = $savedForLater->create($data);
        if ($savedForLater->hasErrors()) {
            return $this->sendError($savedForLater->response['statusCode'], $savedForLater->response['message'], $savedForLater->errors);
        }
        
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            SavedForLaterResource::format($savedForLater)
        );
    }

    public function destroy()
    {
        $savedForLater = new SavedForLater;
        $savedForLater->id = $this->f3->get('PARAMS.id');
        $savedForLater = $savedForLater->retrieveAndCheckForAccount($this->accountId);
        if ($savedForLater->hasErrors()) {
            return $this->sendError($savedForLater->response['statusCode'], $savedForLater->response['message'], $savedForLater->errors);
        }
        $savedForLater->erase();

        return $this->sendSuccess(
            Constants::HTTP_OK,
            'deleted successfully'
        );
    }

    public function moveToCart()
    {
        $savedForLater = new SavedForLater;
        $savedForLater->id = $this->f3->get('PARAMS.id');
        $savedForLater = $savedForLater->retrieveAndCheckForAccount($this->accountId);
        if ($savedForLater->hasErrors()) {
            return $this->sendError($savedForLater->response['statusCode'], $savedForLater->response['message'], $savedForLater->errors);
        }
        $cartDetail = new CartDetail;
        $cartDetail->create([
            'userId' => $this->objUser->id,
            #'accountId' => $this->accountId,
            'entityProductId' => $savedForLater->entityProductId->id,
            'quantity' => $savedForLater->quantity,
        ]);
        if ($cartDetail->hasErrors()) {
            return $this->sendError($cartDetail->response['statusCode'], $cartDetail->response['message'], $savedForLater->errors);
        }

        $savedForLater->erase();
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            CartDetailResource::format($cartDetail)
        );
    }
}