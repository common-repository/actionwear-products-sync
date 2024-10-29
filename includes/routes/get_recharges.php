<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge as Recharge;

$route = new Action_Wear_Api_Route("/get_recharges");
$route->setMethods("GET");
$route->setCallback(function () {
  $response = new ApiResponse();
  $configured = Recharge::hasConfiguredListType() && Recharge::hasConfiguredRechargeType();

  $recharges = Recharge::getAll();

  $response->success([
    "list_type" => Recharge::getListTypeConfigured(),
    "recharge_type" => Recharge::getRechargeTypeConfigured(),
    "is_configured" => $configured,
    "recharges" => $recharges
  ]);
  return $response->getResponse();
});
$route->registerRoute();
