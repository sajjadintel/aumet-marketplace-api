<?php






#####################################################
##### OLD ROUTES FROM PREVIOUS PROJECT
#####################################################

#################
## User Endpoints
$f3->route('GET /v1/user/profile', 'UserController->getProfile');

$f3->route('POST /v1/user/forceupdate', 'UserController->postForceNewPasswordProtocol');

$f3->route('POST /v1/user/signup', 'UserController->postSignUp');
$f3->route('POST /v1/user/signin', 'UserController->postSignIn');
$f3->route('POST /v1/user/signout', 'UserController->postSignOut');
$f3->route('POST /v1/user/profile', 'UserController->postUpdateProfile');
$f3->route('POST /v1/user/password', 'UserController->postUpdatePassword');
$f3->route('POST /v1/user/requestPasswordReset', 'UserController->postRequestPasswordReset');
$f3->route('POST /v1/user/verifyCode', 'UserController->postVerifyCode');
$f3->route('POST /v1/user/resetPassword', 'UserController->postResetPassword');

####################
## Display Endpoints
$f3->route('GET /v1/display/pages/@id', 'DisplayController->getTextPage');
$f3->route('GET /v1/display/testimonials/@typeId', 'DisplayController->getTextIconTestimonials');

$f3->route('GET /v1/display/faq', 'DisplayController->getTextFaq');
$f3->route('GET /v1/display/homePromo', 'DisplayController->getTextIconHomePromo');
$f3->route('GET /v1/display/whyChooseUs', 'DisplayController->getTextIconWhyChooseUs');
$f3->route('GET /v1/display/setApart', 'DisplayController->getTextIconSetApart');
$f3->route('GET /v1/display/makeMoneyWithUs', 'DisplayController->getTextIconMakeMoneyWithUs');


$f3->route('GET /v1/display/contentLanguages', 'DisplayController->getTextContentLanguages');
$f3->route('GET /v1/display/contentTags', 'DisplayController->getTextContentTags');

#################
## Poet Endpoints
$f3->route('GET /v1/poets/all', 'PoetController->getPoetList');
$f3->route('GET /v1/poets/all/@limit', 'PoetController->getPoetList');
$f3->route('GET /v1/poets/all/@limit/@offset', 'PoetController->getPoetList');
$f3->route('GET /v1/poets/all/@limit/@offset/@sortBy', 'PoetController->getPoetList');
$f3->route('GET /v1/poets/all/@limit/@offset/@sortBy/@nationality', 'PoetController->getPoetList');
$f3->route('GET /v1/poets/all/@limit/@offset/@sortBy/@nationality/@featured', 'PoetController->getPoetList');

$f3->route('GET /v1/poets/@id', 'PoetController->getPoetDetails');
$f3->route('GET /v1/poets/nationalities', 'PoetController->getPoetNationalityList');

#################
## Book Endpoints
$f3->route('GET /v1/books/categories', 'BookController->getBookCategories');
$f3->route('GET /v1/books/subcategories', 'BookController->getBookSubcategories');
$f3->route('GET /v1/books/subcategories/categories/@id', 'BookController->getBookSubcategoriesOfCategory');

$f3->route('GET /v1/books/all', 'BookController->getBookList');
$f3->route('GET /v1/books/all/@limit', 'BookController->getBookList');
$f3->route('GET /v1/books/all/@limit/@offset', 'BookController->getBookList');
$f3->route('GET /v1/books/all/@limit/@offset/*', 'BookController->getBookList');

$f3->route('GET /v1/books/@id', 'BookController->getBookDetails');
$f3->route('GET /v1/books/reviews/@id', 'BookController->getBookReviews');

$f3->route('POST /v1/books/views', 'BookController->postUpdateBookViews');
$f3->route('POST /v1/books/reviews', 'BookController->postAddReview');

###################
## Search Endpoints
$f3->route('GET /v1/search/@search', 'SearchController->getSearchResults');
$f3->route('GET /v1/search/@type/@search', 'SearchController->getSearchResults');
$f3->route('GET /v1/search/@type/@limit/@search', 'SearchController->getSearchResults');
$f3->route('GET /v1/search/@type/@limit/@offset/@search', 'SearchController->getSearchResults');
$f3->route('GET /v1/search/@type/@limit/@offset/@sort/@search', 'SearchController->getSearchResults');
