<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Action_Wear_Core;


$route = new Action_Wear_Api_Route("/resync_prices");
$route->setMethods("POST");
$route->setCallback(function () {

    global $wpdb;

    $skus = $wpdb->get_results("SELECT sku FROM {$wpdb->prefix}actionwear_products WHERE wc_created = 1");
    foreach ($skus as $sku) {
        Action_Wear_Core::$pricesQueue->push_to_queue($sku->sku);
    }
    Action_Wear_Core::$pricesQueue->save();

    $response = new ApiResponse();
    $response->success([], "Prices update task created!");
    return $response->getResponse();
});
$route->registerRoute();
