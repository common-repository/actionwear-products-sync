<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;

$route = new Action_Wear_Api_Route("/get_products_language");
$route->setMethods("GET");
$route->setCallback(function () {

    $products_language = get_option('_ACTIONWEAR_PRODUCTS_LANGUAGE');

    $response = new ApiResponse();
    $response->success([
        "products_language" => $products_language
    ]);
    return $response->getResponse();
});
$route->registerRoute();
