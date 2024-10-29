<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Sync\Action_Wear_Sync as Sync;
use AC_SYNC\Includes\Classes\Sync\Action_Wear_Queue as Queue;
use AC_SYNC\Includes\Classes\Attribute\Action_Wear_Attribute as Attribute;
use AC_SYNC\Includes\Classes\Utils\Action_Wear_Circuit_Breaker as CircuitBreaker;

$route = new Action_Wear_Api_Route("/get_login_status");
$route->setMethods("GET");
$route->setCallback(function () {
  $response = new ApiResponse();

  $apikey = get_option("_ACTIONWEAR_APIKEY");
  $onBoarding = (int) get_option("_ACTIONWEAR_ONBOARDING");
  $initialSyncProgress = (int) get_option("_ACTIONWEAR_INITIAL_SYNC_PROGRESS");
  $isValidApikey = Action_Wear_Api::isValidApikey($apikey);
  $isProcessingProducts = Sync::isProcessingAnymore();
  $version = \AC_SYNC\Action_Wear_Core::$version;
  $products_lang = get_option('_ACTIONWEAR_PRODUCTS_LANG');

  $data = [
    "is_valid" => $isValidApikey,
    "onBoarding" => $onBoarding,
    "initialSyncProgress" => $initialSyncProgress,
    "is_processing" => $isProcessingProducts,
    "products_language" => $products_lang
  ];

  $processed = Sync::getNbProductsProcessed();
  $total = Sync::getNbProductsTotal();
  $data["to_process"] = $total - $processed;
  $data["processed"] = $processed;
  $data["total"] = $total;
  $data["last_sync"] = Sync::getLastSync();
  $data["orders_received"] = Sync::getActionWearOrders();
  $data["version"] = $version;
  $data["attributes"] = Attribute::getAttributesWithoutDefaults();
  $data["queueCount"] = count(Queue::getQueue());
  $data["circuitOpen"] = (int) get_option(CircuitBreaker::CACHE_KEY, 0) === 3;

  $response->success($data);
  return $response->getResponse();
});
$route->registerRoute();
