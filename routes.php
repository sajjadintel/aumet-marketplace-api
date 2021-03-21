<?php

###################
## General Endpoints
$f3->route('GET /v1/app/settings', 'AppController->getAppDetails');
$f3->route('GET /v1/app/menu', 'AppController->getMenu');
$f3->route('GET /v1/app/menu/section', 'AppController->getMenuSection');

###################
## User Endpoints
$f3->route('POST /v1/users/signin', 'UserController->postSignIn');
$f3->route('POST /v1/users/signinTest', 'UserController->postSignInTest');
$f3->route('POST /v1/users/signup', '');
$f3->route('POST /v1/users/password/forgot', '');
$f3->route('POST /v1/users/password/reset', '');
$f3->route('POST /v1/users/signout', 'UserController->postSignOut');
$f3->route('GET /v1/users/profile', 'UserController->getProfile');
$f3->route('GET /v1/user/pharmacy', 'UserPharmacyController->index');

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
## FAQ
$f3->route('GET /v1/pharmacy/faq', 'FaqController->index');