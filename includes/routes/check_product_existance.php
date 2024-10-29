<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
//NOTE: Questo script potrebbe creare problemi di memory_limit
require_once(ABSPATH . '/wp-admin/includes/file.php');

use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
use AC_SYNC\Includes\Classes\Sync\Action_Wear_Sync as Sync;
use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
use AC_SYNC\Includes\Classes\Sql\Action_Wear_Sql_Manager;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Setting\Action_Wear_Setting_Debug_Mode as DebugMode;

$route = new Action_Wear_Api_Route("/check_product_existance");
$route->setMethods("GET");
$route->setCallback(function () {

  $response = new ApiResponse();

  if (Sync::isLocked()) {
    $response->fail("The sync process is already started and locked");
    return $response->getResponse();
  }

  Sync::lock();
  @ignore_user_abort(true);
  @set_time_limit(300);
  @ini_set('memory_limit', '512M');

  if (Sync::getProgress() === Sync::INITIAL) {
    if (DebugMode::getDebugMode())
      Log::write("Procedura di popolamento avviata", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
    Action_Wear_Sql_Manager::truncateAll(["actionwear_log"]);
    if (DebugMode::getDebugMode())
      Log::write("Tabelle svuotate", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
    try {
      // @deprecated removed on this step, brands are now created during product creation
      // Product::generateBrands();
    } catch (\Exception $e) {
      update_option("_ACTIONWEAR_API_ERROR", 1);
      $response->fail($e->getMessage());
      Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_PRODUCT_CREATION);
      return new WP_REST_Response($response->getResponse(), 422);
    }
    if (DebugMode::getDebugMode())
      Log::write("Brand generati", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
    Sync::setProgress(Sync::BRANDS_GENERATED);
  }

  if (Sync::getProgress() === Sync::BRANDS_GENERATED) {
    try {
      Product::generateCategories();
    } catch (\Exception $e) {
      update_option("_ACTIONWEAR_API_ERROR", 1);
      $response->fail($e->getMessage());
      Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_PRODUCT_CREATION);
      return new WP_REST_Response($response->getResponse(), 422);
    }
    if (DebugMode::getDebugMode())
      Log::write("Categorie generate", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
    Sync::setProgress(Sync::CATEGORIES_GENERATED);
  }

  if (Sync::getProgress() === Sync::CATEGORIES_GENERATED) {
    try {
      Product::generateProducts();
    } catch (\Exception $e) {
      update_option("_ACTIONWEAR_API_ERROR", 1);
      $response->fail($e->getMessage());
      Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_PRODUCT_CREATION);
      return new WP_REST_Response($response->getResponse(), 422);
    }
    if (DebugMode::getDebugMode())
      Log::write("Prodotti ed associazioni con categorie generate", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
    Sync::setProgress(Sync::PRODUCTS_GENERATED);
  }

  if (Sync::getProgress() === Sync::PRODUCTS_GENERATED) {
    try {
      Product::generateImages();
    } catch (\Exception $e) {
      update_option("_ACTIONWEAR_API_ERROR", 1);
      $response->fail($e->getMessage());
      Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_PRODUCT_CREATION);
      return new WP_REST_Response($response->getResponse(), 422);
    }
    if (DebugMode::getDebugMode())
      Log::write("Immagini e relazioni interne generate", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
    Sync::setProgress(Sync::IMAGES_GENERATED);
    if (DebugMode::getDebugMode())
      Log::write("Procedura di popolamento completata", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
  }

  Sync::unlock();

  $response->success("Sync finish");
  return $response->getResponse();
});
$route->registerRoute();
