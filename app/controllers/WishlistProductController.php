<?php

use App\Models\CartDetail;
use App\Models\EntityProductAccountWishlist;
use App\Models\EntityProductSell;
use App\Models\User;
use App\Resources\CartDetailResource;
use App\Resources\WishlistResource;

class WishlistProductController extends MainController
{
    public function index()
    {
        $user = new User;
        $user->id = $this->objUser->id;
        
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            WishlistResource::collection(
                $user->wishlist()
            )
        );
    }

    public function show()
    {
        $wishlistProduct = new EntityProductAccountWishlist;
        $wishlistProduct = $wishlistProduct->retrieveAndCheckForAccount($this->objUser->accountId, $this->f3->get('PARAMS.id'));
        if ($wishlistProduct->hasErrors()) {
            return $this->sendError($wishlistProduct->response['statusCode'], $wishlistProduct->response['message'], $wishlistProduct->errors);
        }

        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            WishlistResource::format($wishlistProduct)
        );
    }

    public function create()
    {
        $data = (array) $this->requestData;
        $data = array_merge($data, ['account_id' => $this->objUser->accountId]);
        $wishlistProduct = new EntityProductAccountWishlist;
        $wishlistProduct = $wishlistProduct->create($data);
        if ($wishlistProduct->hasErrors()) {
            return $this->sendError($wishlistProduct->response['statusCode'], $wishlistProduct->response['message'], $wishlistProduct->errors);
        }
        
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            WishlistResource::format($wishlistProduct)
        );
    }

    public function destroy()
    {
        $wishlistProduct = new EntityProductAccountWishlist;
        $wishlistProduct = $wishlistProduct->retrieveAndCheckForAccount($this->objUser->accountId, $this->f3->get('PARAMS.id'));
        if ($wishlistProduct->hasErrors()) {
            return $this->sendError($wishlistProduct->response['statusCode'], $wishlistProduct->response['message'], $wishlistProduct->errors);
        }
        $wishlistProduct->erase();

        return $this->sendSuccess(
            Constants::HTTP_OK,
            'deleted successfully'
        );
    }

    public function moveToCart()
    {
        $wishlistProduct = new EntityProductAccountWishlist;
        $wishlistProduct = $wishlistProduct->retrieveAndCheckForAccount($this->objUser->accountId, $this->f3->get('PARAMS.id'));
        if ($wishlistProduct->hasErrors()) {
            return $this->sendError($wishlistProduct->response['statusCode'], $wishlistProduct->response['message'], $wishlistProduct->errors);
        }
        $cartDetail = new CartDetail;
        $cartDetail->create([
            'userId' => $this->objUser->id,
            'accountId' => $this->objUser->accountId,
            'entityProductId' => $wishlistProduct->entityProductId->id,
            'quantity' => $wishlistProduct->quantity,
        ]);
        if ($cartDetail->hasErrors()) {
            return $this->sendError($cartDetail->response['statusCode'], $cartDetail->response['message'], $cartDetail->errors);
        }

        $wishlistProduct->erase();
        return $this->sendSuccess(
            Constants::HTTP_OK,
            'success',
            CartDetailResource::format($cartDetail)
        );
    }
}