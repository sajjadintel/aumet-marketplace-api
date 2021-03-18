<?php

###################
## General Endpoints
$f3->route('GET /v1/app/settings', 'AppController->getAppDetails');
$f3->route('GET /v1/app/menu', 'AppController->getMenu');
$f3->route('GET /v1/app/menu/section', 'AppController->getMenuSection');

###################
## User Endpoints
$f3->route('POST /v1/users/signin', 'UserController->postSignIn');
$f3->route('POST /v1/users/signup', '');
$f3->route('POST /v1/users/password/forgot', '');
$f3->route('POST /v1/users/password/reset', '');
$f3->route('POST /v1/users/signout', 'UserController->postSignOut');
$f3->route('GET /v1/users/profile', 'UserController->getProfile');

##################################################
## PHARMACY ACCESS

###################
## Products Endpoints
$f3->route('GET /v1/pharmacy/products', 'ProductController->getProducts');
$f3->route('GET /v1/pharmacy/products/@id', 'ProductController->getProduct');
$f3->route('GET /v1/pharmacy/products/bonus/@productId', 'ProductController->getProductBonus');

#################
## Cart Endpoints
$f3->route('GET /v1/pharmacy/cart', 'CartController->getCartItems');
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
$f3->route('GET /v1/pharmacy/messages', 'MessageController->getChatRooms');
$f3->route('GET /v1/pharmacy/messages/@id', 'MessageController->getMessages');
$f3->route('POST /v1/pharmacy/messages/unread', 'MessageController->unreadMessages');
$f3->route('POST /v1/pharmacy/messages/read', 'MessageController->readMessages');
$f3->route('POST /v1/pharmacy/messages/archive', 'MessageController->archiveChatRoom');
$f3->route('POST /v1/pharmacy/messages', 'MessageController->newMessage');
$f3->route('POST /v1/pharmacy/messages/chatroom', 'MessageController->newChatRoom');