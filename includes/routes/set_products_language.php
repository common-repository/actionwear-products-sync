<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;


$route = new Action_Wear_Api_Route("/set_products_language");
$route->setMethods("POST");
$route->setCallback(function () {

    $response = new ApiResponse();

    $languageObject = json_decode(file_get_contents("php://input"), true);
    $products_lang = $languageObject['body'];

    update_option('_ACTIONWEAR_PRODUCTS_LANG', $products_lang);

    $response->success([$products_lang], 'Ok!');
    return $response->getResponse();
});
$route->registerRoute();
