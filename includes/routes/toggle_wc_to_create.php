<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use AC_SYNC\Action_Wear_Core;
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;


$route = new Action_Wear_Api_Route("/toggle_wc_to_create");
$route->setMethods("POST");
$route->setCallback(function () {
  global $wpdb;
  $response = new ApiResponse();
  $data = json_decode(file_get_contents("php://input"));

  $id = $data->id;

  try {
    $current = (int)$wpdb->get_var("SELECT wc_to_create FROM {$wpdb->prefix}actionwear_products WHERE id = $id");
    $wpdb->update("{$wpdb->prefix}actionwear_products", [
      "wc_to_create" => $current === 0 ? 1 : 0,
      "wc_created" => 0
    ], [
      "id" => $id
    ]);
    if ($current === 0) {
      $sku = $wpdb->get_var("SELECT sku FROM {$wpdb->prefix}actionwear_products WHERE id = $id");
      try {
        $product = Product::getRemoteProductsListino([$sku]);
        Action_Wear_Core::$importProcess->push_to_queue($product->{$sku});
        Action_Wear_Core::$importProcess->save();
      } catch (\Throwable $e) {
        Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_GENERAL, __LINE__, __FILE__);
      }
    }
  } catch (\Exception $e) {
    $response->fail($e->getMessage());
    return new WP_REST_Response($response->getResponse(), 422);
  }

  $response->success();
  return $response->getResponse();
});
$route->registerRoute();
