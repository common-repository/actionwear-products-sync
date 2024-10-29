<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge as Recharge;
use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge_Type as RechargeType;

$route = new Action_Wear_Api_Route("/update_recharge_details");
$route->setMethods("POST");
$route->setCallback(function () {

  $response = new ApiResponse();
  $data = json_decode(file_get_contents("php://input"));

  try {

    $id = (int)$data->recharge->id;
    $type = new RechargeType($data->recharge->recharge_type);
    $recharge = new Recharge($type, (int)$data->recharge->recharge_entity_id, $id);
    $recharge->setDetails($data->recharge->details);
    $recharge->updateDetails();

    $response->success([
      "recharge" => $recharge->getRaw()
    ]);
  } catch (\Exception $e) {
    $response->fail($e->getMessage());
    return new WP_REST_Response($response->getResponse(), 422);
  }

  return $response->getResponse();
});
$route->registerRoute();
