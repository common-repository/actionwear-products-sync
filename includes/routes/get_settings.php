<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Setting\Action_Wear_Setting as Setting;

$route = new Action_Wear_Api_Route("/get_settings");
$route->setMethods("GET");
$route->setCallback(function () {
  $response = new ApiResponse();
  $settings = Setting::getAllSettings();
  $response->success([
    "settings" => $settings
  ]);
  return $response->getResponse();
});
$route->registerRoute();
