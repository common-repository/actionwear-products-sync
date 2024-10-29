<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;

$route = new Action_Wear_Api_Route("/get_selected_taxonomies");
$route->setMethods("GET");
$route->setCallback(function () {

    $selected_taxonomies = get_option('_ACTIONWEAR_SELECTED_TAXONOMIES');

    $response = new ApiResponse();
    $response->success([
        "selected_taxonomies" => $selected_taxonomies
    ]);
    return $response->getResponse();
});
$route->registerRoute();
