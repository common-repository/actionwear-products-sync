<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api;
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Utils\Action_Wear_Utils as Utils;
use AC_SYNC\Includes\Classes\Sync\Action_Wear_Sync as Sync;

$route = new Action_Wear_Api_Route("/preliminary_checks");
$route->setMethods("GET");
$route->setCallback(function () {


  $response = new ApiResponse();

  $memory_limit = true;
  $memory = ini_get("memory_limit");
  $memory_value = Utils::convertMemoryValues($memory);
  if ($memory_value > 1) {
    $check = true;
  } else {
    $check = false;
  }
  // this environment is route of an API call and check the possibility to set the memory limit, don't modify the running environment, it's used just for check
  if ($check === false)
    $memory_limit = ini_set('memory_limit', '512M') !== false;

  // this environment is a route of an API call and check the possibility to set the time limit, don't modify the running environment, it's used just for check
  $execution_time = true;
  if (ini_get("max_execution_time") < 300)
    $execution_time = set_time_limit(300);

  $current_limit = ini_get("max_execution_time");
  if ($current_limit !== false)
    $current_limit = (int) $current_limit;

  $zip = class_exists("ZipArchive");

  $wc_exist = class_exists("woocommerce");

  $external_configuration = wp_remote_get(Action_Wear_API::EXTERNAL_CONFIGURATION, ["timeout" => 5]);
  if (!is_wp_error($external_configuration)) {
    $external_configuration = json_decode($external_configuration["body"]);
    $resync_dates = $external_configuration->resync_all;
    $external_configuration->resync_all = Sync::getResyncStatusByDates($resync_dates);
  } else {
    $external_configuration = false;
  }

  $response->success([
    "memory_limit" => $memory_limit,
    "execution_time" => $execution_time,
    "current" => $current_limit,
    "zip" => $zip,
    "wc_exist" => $wc_exist,
    "external_configuration" => $external_configuration
  ]);
  return $response->getResponse();
});
$route->registerRoute();
