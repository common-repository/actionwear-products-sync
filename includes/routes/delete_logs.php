<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;

$route = new Action_Wear_Api_Route("/delete_logs");
$route->setMethods("GET");
$route->setCallback(function () {
  $response = new ApiResponse();
  Log::truncateLogs();
  $response->success([], "Tutti i logs sono stati eliminati con successo");
  return $response->getResponse();
});
$route->registerRoute();
