<?php

###################
## General Endpoints
$f3->route('GET /v1/app/settings', 'AppController->getAppDetails');
$f3->route('GET /v1/app/menu', 'AppController->getMenu');
$f3->route('GET /v1/app/menu/section', 'AppController->getMenuSection');

###################
## Geo Endpoints
$f3->route('GET /v1/geo/country', 'GeoController->getCountryList');
$f3->route('GET /v1/geo/city', 'GeoController->getCityList');

###################
## User Endpoints
$f3->route('POST /v1/users/signup', 'UserController->postSignUp');
$f3->route('POST /v1/users/signin', 'UserController->postSignIn');
$f3->route('POST /v1/users/signinTest', 'UserController->postSignInTest');
$f3->route('POST /v1/users/password/forgot', 'UserController->postForgottenPassword');
$f3->route('POST /v1/users/password/reset', 'UserController->postResetPassword');
$f3->route('GET /v1/users/profile', 'UserController->getProfile');
$f3->route('POST /v1/users/edit', 'UserController->postUpdateUser');
$f3->route('POST /v1/users/uploadDocument', 'UserController->postSignUpDocumentUpload');
$f3->route('POST /v1/users/uploadEntityImage', 'UserController->postEntityImageUpload');
$f3->route('POST /v1/users/uploadEntityDocument', 'UserController->postEntityDocumentUpload');
$f3->route('POST /v1/users/signout', 'UserController->postSignOut');

###################
## User Pharmacy Endpoints
$f3->route('GET /v1/users/pharmacy', 'UserPharmacyController->index');
$f3->route('POST /v1/users/pharmacy/edit', 'ProfileController->postRequestUpdatePharmacyProfile');

##################################################
## PHARMACY ACCESS

###################
## Products Endpoints
$f3->route('GET /v1/pharmacy/products', 'ProductController->getProducts');
$f3->route('GET /v1/pharmacy/products/@id', 'ProductController->getProduct');

#################
## Cart Endpoints
$f3->route('GET /v1/pharmacy/cart', 'CartController->getCartItems');
$f3->route('GET /v1/pharmacy/cart-v2', 'CartController->getCartItemsV2');
$f3->route('POST /v1/pharmacy/cart/product', 'CartController->postAddProduct');
$f3->route('POST /v1/pharmacy/cart/bonus', 'CartController->postAddBonus');
$f3->route('POST /v1/pharmacy/cart/delete', 'CartController->postDeleteItem');

###################
## Orders Endpoints
$f3->route('GET /v1/pharmacy/orders', 'OrderController->getOrders');
$f3->route('GET /v1/pharmacy/orders/@id', 'OrderController->getOrder');
$f3->route('POST /v1/pharmacy/orders', 'OrderController->postOrder');
$f3->route('POST /v1/pharmacy/orders/cancel', 'OrderController->postOrderCancel');
$f3->route('POST /v1/pharmacy/orders/reportmissing', 'OrderController->postReportMissing');
$f3->route('POST /v1/pharmacy/orders/edit', 'OrderController->postOrderEdit');

###################
## Feedback Endpoints
$f3->route('GET /v1/pharmacy/feedback', 'FeedbackController->getFeedbacks');
$f3->route('POST /v1/pharmacy/feedback', 'FeedbackController->postFeedback');

###################
## Search Endpoints
$f3->route('GET /v1/pharmacy/sellers', 'SearchController->getSellerList');

###################
## News Endpoints
$f3->route('GET /v1/pharmacy/news', 'NewsController->getNewsList');
$f3->route('GET /v1/pharmacy/news/@id', 'NewsController->getNews');
$f3->route('GET /v1/pharmacy/newsType', 'NewsController->getNewsTypeList');

###################
## Message Endpoint
$f3->route('GET /v1/pharmacy/messages', 'MessageController->getChatRoomList');
$f3->route('GET /v1/pharmacy/messages/@id', 'MessageController->getMessageList');
$f3->route('POST /v1/pharmacy/messages/unread', 'MessageController->postSetMessagesUnread');
$f3->route('POST /v1/pharmacy/messages/read', 'MessageController->postSetMessagesRead');
$f3->route('POST /v1/pharmacy/messages/archive', 'MessageController->postSetChatRoomArchive');
$f3->route('POST /v1/pharmacy/messages', 'MessageController->postNewMessage');
$f3->route('POST /v1/pharmacy/messages/chatroom', 'MessageController->postNewChatRoom');

###################
## FAQ
$f3->route('GET /v1/pharmacy/faq', 'FaqController->index');

###################
## Wishlist Endpoints
$f3->route('GET /v1/pharmacy/wishlist', 'WishlistController->index');
$f3->route('POST /v1/pharmacy/wishlist', 'WishlistController->create');
$f3->route('DELETE /v1/pharmacy/wishlist/@id', 'WishlistController->destroy');
$f3->route('POST /v1/pharmacy/wishlist/@id/cart', 'WishlistController->moveToCart');

###################
## Notifications Endpoints
$f3->route('GET /v1/pharmacy/notification', 'NotificationsController->index');
$f3->route('PATCH /v1/pharmacy/notification/@id/read', 'NotificationsController->markAsRead');
$f3->route('POST /v1/pharmacy/support', 'NotificationsController->postSupport');

###################
## Distributor Endpoints
$f3->route('GET /v1/pharmacy/distributor', 'DistributorsController->index');
