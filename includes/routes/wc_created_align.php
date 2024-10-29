<?php
@ignore_user_abort(true);
@set_time_limit(300);
@ini_set('memory_limit', '1G');

use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;

$route = new Action_Wear_Api_Route("/wc_created_align");
$route->setMethods("GET");
$route->setCallback(function () {
  global $wpdb;
  $response = new ApiResponse();
  $real_skus = [];
  $products = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "actionwear_products");
  $skus = $wpdb->get_col("SELECT meta_value FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_sku'");
  foreach ($products as $product) {
    if (in_array($product->sku, $skus)) {
      $real_skus[] = $product->sku;
    }
  }

  $query = "UPDATE " . $wpdb->prefix . "actionwear_products SET wc_created = 1, wc_to_create = 1 WHERE sku IN ('" . implode("','", $real_skus) . "')";
  $wpdb->query($query);

  $response->success([
    "success" => true
  ]);

  return $response->getResponse();
});
$route->registerRoute();
