<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;

$route = new Action_Wear_Api_Route("/get_initial_sync_progress");
$route->setMethods("GET");
$route->setCallback(function () {
  $response = new ApiResponse();
  $progress = get_option("_ACTIONWEAR_INITIAL_SYNC_PROGRESS");
  $response->success([
    "progress" => $progress
  ]);
  if (get_option("_ACTIONWEAR_API_ERROR")) {
    $response->fail("Error");
    return new WP_REST_Response($response->getResponse(), 500);
  }
  return $response->getResponse();
});
$route->registerRoute();
