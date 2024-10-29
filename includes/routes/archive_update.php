<?php
ignore_user_abort(true);
set_time_limit(300);
ini_set('memory_limit', '1G');

use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
use AC_SYNC\Action_Wear_Core as Core;

$route = new Action_Wear_Api_Route("/archive_update");
$route->setMethods("GET");
$route->setCallback(function () {

  $response = new ApiResponse();
  global $wpdb;


  try {
    $wpdb->query("TRUNCATE " . $wpdb->prefix . "actionwear_images");
    $wpdb->query("TRUNCATE " . $wpdb->prefix . "actionwear_categories_product");
    Product::filterDeletedProducts();
    Core::$differentialProductQueue->push_to_queue('generateCategories');
    Core::$differentialProductQueue->push_to_queue('generateProducts');
    Core::$differentialProductQueue->push_to_queue('generateImages');
    Core::$differentialProductQueue->save();
    Log::write("Aggiunti gli aggiornamenti di brands, categorie, prodotti ed immagini alla coda", Log::INFO, Log::CONTEXT_CRONJOB);
  } catch (\Exception $e) {
    Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_PRODUCT_CREATION);
  }

  $response->success([
    "success" => true
  ]);

  return $response->getResponse();
});
$route->registerRoute();
