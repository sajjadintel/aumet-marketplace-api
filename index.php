<?php

require_once("vendor/autoload.php");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Reports all errors
error_reporting(E_ALL);
// Do not display errors for the end-users (security issue)
ini_set('display_errors', 'On');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Requested-With, x-api-key, x-access-token, x-session-id, x-user-lang, x-api-os');
define('CHUNK_SIZE', 1024 * 1024);

date_default_timezone_set("UTC");

$f3 = Base::instance();

// F3 CONFIG 
$arrAutoloadFolders = [
    "app/controllers/",
    "app/models/",
    "app/classes/"
];
$f3->set('AUTOLOAD', implode("|", $arrAutoloadFolders));

$f3->set('DEBUG', '9');
$f3->set('PREFIX', 'RESPONSE.');
$f3->set('LOCALES', 'app/dict/');
$f3->set('UI', 'app/ui/');
$f3->set('pagesDIR', 'ui/pages');
$f3->set('LOGS', 'logs/');
$f3->set('FALLBACK', 'ar');

$f3->set('ENCODING', 'UTF-8');

$uploadsDir = dirname(__FILE__) . '/files/uploads/';
if (is_dir($uploadsDir)) {
    $f3->set('uploadDIR', $uploadsDir);
} else {
    if (!mkdir($uploadsDir, 0777, true)) {
        die('Failed to create folders...');
    } else {
        $f3->set('uploadDIR', $uploadsDir);
    }
}

$f3->set('platformVersionRelease', '?v=1.3');
$f3->set('platformVersionDevelopment', '?v=' . date('His'));

$f3->set('platformVersion', $f3->get('platformVersionDevelopment'));


// Environment Values
switch (getenv('ENV')) {
    case Constants::ENV_LOCAL:
        $f3->set('ROOT_URL', getenv('ROOT_URL_LOC'));
        $f3->set('API_URL', getenv('API_URL_LOC'));
        $dbHost = getenv('DB_HOST_LOC');
        $dbUsername = getenv('DB_USER_LOC');
        $dbPassword = getenv('DB_PASS_LOC');
        break;
    case Constants::ENV_DEV:
        $f3->set('ROOT_URL', getenv('ROOT_URL_DEV'));
        $f3->set('API_URL', getenv('API_URL_DEV'));
        $dbHost = getenv('DB_HOST_DEV');
        $dbUsername = getenv('DB_USER_DEV');
        $dbPassword = getenv('DB_PASS_DEV');
        break;
    case Constants::ENV_BETA:
        $f3->set('ROOT_URL', getenv('ROOT_URL_BETA'));
        $f3->set('API_URL', getenv('API_URL_BETA'));
        $dbHost = getenv('DB_HOST_BETA');
        $dbUsername = getenv('DB_USER_BETA');
        $dbPassword = getenv('DB_PASS_BETA');
        break;
    case Constants::ENV_PROD:
        $f3->set('ROOT_URL', getenv('ROOT_URL_PROD'));
        $f3->set('API_URL', getenv('API_URL_PROD'));
        $dbHost = getenv('DB_HOST_PROD');
        $dbUsername = getenv('DB_USER_PROD');
        $dbPassword = getenv('DB_PASS_PROD');
        break;
    default:
        die("Invalid ENV value");
        break;
}

// Connect Info
$dbName = getenv('DB_NAME_MAIN');
$dbPort = getenv('DB_PORT');

$f3->set('dbUsername', $dbUsername);
$f3->set('dbPassword', $dbPassword);
$f3->set('dbConnectionStringMain', "mysql:host=$dbHost;port=$dbPort;dbname=$dbName");

$f3->set("dbConnectionMain", new DB\SQL(
    $f3->get('dbConnectionStringMain'),
    $f3->get('dbUsername'),
    $f3->get('dbPassword'),
    array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
));

include_once("routes.php");

session_start();

// $f3->set(
//     'ONERROR',
//     function ($f3) {

//         header('Content-Type: application/json');
//         // ok, validation error, or failure
//         header('Status: 200 OK');
//         // return the encoded json
//         echo json_encode(array(
//             'statusCode' =>  $f3->get('ERROR.code'), // success or not?
//             'message' => $f3->get('ERROR.text'),
//             'data' => null
//         ));
//     }
// );

$f3->run();
