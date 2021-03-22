<?php

use App\Models\CartDetail;
use App\Models\EntityProductAccountWishlist;
use App\Models\User;
use App\Resources\CartDetailResource;
use App\Resources\WishlistResource;

class WishlistController extends MainController
{
    public function index()
    {
        $user = new User;
        $user->id = $this->objUser->id;
        
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            WishlistResource::collection(
                $user->savedForLater()
            )
        );
    }

    public function create()
    {
        $data = (array) $this->requestData;
        $data = array_merge($data, ['account_id' => $this->objUser->accountId]);
        $savedForLater = new EntityProductAccountWishlist;
        $savedForLater = $savedForLater->create($data);
        if ($savedForLater->hasErrors()) {
            return $this->sendError($savedForLater->response['statusCode'], $savedForLater->response['message'], $savedForLater->errors);
        }
        
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            WishlistResource::format($savedForLater)
        );
    }

    public function destroy()
    {
        $savedForLater = new EntityProductAccountWishlist;
        $savedForLater->id = $this->f3->get('PARAMS.id');
        $savedForLater = $savedForLater->retrieveAndCheckForAccount($this->objUser->accountId);
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
        $savedForLater = new EntityProductAccountWishlist;
        $savedForLater->id = $this->f3->get('PARAMS.id');
        $savedForLater = $savedForLater->retrieveAndCheckForAccount($this->objUser->accountId);
        if ($savedForLater->hasErrors()) {
            return $this->sendError($savedForLater->response['statusCode'], $savedForLater->response['message'], $savedForLater->errors);
        }
        $cartDetail = new CartDetail;
        $cartDetail->create([
            'userId' => $this->objUser->id,
            'accountId' => $this->objUser->accountId,
            'entityProductId' => $savedForLater->entityProductId->id,
            'quantity' => $savedForLater->quantity,
        ]);
        if ($cartDetail->hasErrors()) {
            return $this->sendError($cartDetail->response['statusCode'], $cartDetail->response['message'], $cartDetail->errors);
        }

        $savedForLater->erase();
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            CartDetailResource::format($cartDetail)
        );
    }
}