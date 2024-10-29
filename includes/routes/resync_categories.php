<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Category\Action_Wear_Category_Association as CategoryAssociation;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;



$route = new Action_Wear_Api_Route("/resync_categories");
$route->setMethods("POST");
$route->setCallback(function () {

    global $wpdb;

    $sync =  $wpdb->get_results("SELECT sku FROM {$wpdb->prefix}actionwear_products WHERE wc_created = 1");

    foreach ($sync as $s) {
        $_product = new \WC_Product_Variable(wc_get_product_id_by_sku($s->sku));
        $cat_ids = CategoryAssociation::getAssociationByProduct($s->sku);
        $_product->set_category_ids($cat_ids);
        $_product->save();
    }

    $response = new ApiResponse();
    $response->success([], "Categories resync successful!");
    return $response->getResponse();
});
$route->registerRoute();
