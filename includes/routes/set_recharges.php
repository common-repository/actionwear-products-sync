<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge as Recharge;
use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge_Detail as RechargeDetail;
use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge_Type as RechargeType;

$route = new Action_Wear_Api_Route("/set_recharges");
$route->setMethods("POST");
$route->setCallback(function () {

  $response = new ApiResponse();
  $data = json_decode(file_get_contents("php://input"));

  $list_type = $data->list_type;
  $recharge_type = $data->recharge_type;

  try {
    update_option("_ACTIONWEAR_LIST_TYPE_SELECTED", $list_type);
    update_option("_ACTIONWEAR_RECHARGE_TYPE_SELECTED", $recharge_type);
    $type = new RechargeType("global");
    $recharge = new Recharge($type, 0);
    if ($list_type === "listino-pubblico") {
      update_option("_ACTIONWEAR_ONBOARDING", 3);
      update_option("_ACTIONWEAR_RECHARGE_TYPE_SELECTED", "listino-pubblico");
      $price = [
        "price_from" => 0.01,
        "price_to" => 99999
      ];
      $quantity = [
        "quantity_from" => 1,
        "quantity_to" => 99999
      ];
      $percent = 150;
    } else {
      $price = [
        "price_from" => 0.01,
        "price_to" => 5
      ];
      $quantity = [
        "quantity_from" => 1,
        "quantity_to" => 99999
      ];
      $percent = 30;
    }
    $detail = new RechargeDetail($price, $quantity, $percent);
    $detail->addToRecharge($recharge);
    $response->success();
  } catch (\Exception $e) {
    $response->fail($e->getMessage());
    return new WP_REST_Response($response->getResponse(), 422);
  }

  $response->success();
  return $response->getResponse();
});
$route->registerRoute();
