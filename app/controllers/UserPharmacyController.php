<?php

use App\Models\User;
use App\Resources\EntityResource;

class UserPharmacyController extends MainController
{
    public function index()
    {
        $user = new User;
        $user->load(['id = ?', $this->objUser->id]);
    
        return $this->sendSuccess(
            Constants::HTTP_OK, 
            'success', 
            EntityResource::collection($user->pharmacies())
        );
    }
}