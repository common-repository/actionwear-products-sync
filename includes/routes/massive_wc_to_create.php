<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly

use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;


$route = new Action_Wear_Api_Route("/massive_wc_to_create");
$route->setMethods("POST");
$route->setCallback(function () {
  global $wpdb;
  $response = new ApiResponse();
  $data = json_decode(file_get_contents("php://input"));

  $ids = $data->ids;
  $value = (int) $data->value;

  foreach ($ids as $id) {
    try {
      $wpdb->update("{$wpdb->prefix}actionwear_products", [
        "wc_to_create" => $value,
        "wc_created" => 0
      ], [
        "id" => $id
      ]);
    } catch (\Exception $e) {
      $response->fail($e->getMessage());
      return new WP_REST_Response($response->getResponse(), 422);
    }
  }

  if ($value === 1) {
    try {
      $setProductsOp = Product::setProductsToImportBySelection("PRODUCTS", $data);
      if (!$setProductsOp) {
        $response->fail("Errore durante l'import dei prodotti");
        return new WP_REST_Response($response->getResponse(), 422);
      }
    } catch (\Throwable $e) {
      Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_GENERAL, __LINE__, __FILE__);
    }
  }


  $response->success([
    "success" => true,
    "ids" => $ids,
    "value" => $value
  ]);
  return $response->getResponse();
});
$route->registerRoute();
