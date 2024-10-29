<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;

$route = new Action_Wear_Api_Route("/check_actionwear_api");
$route->setMethods("POST");
$route->setCallback(function () {

  $response = new ApiResponse();
  $incoming_data = json_decode(file_get_contents("php://input"));
  $apikey = $incoming_data->apikey;
  $isValidApikey = Action_Wear_Api::isValidApikey($apikey);
  if ($isValidApikey) {
    update_option("_ACTIONWEAR_APIKEY", $apikey);
    Action_Wear_Api::pingInstance();
    $config_option = (int) get_option("_ACTIONWEAR_ONBOARDING");
    if ($config_option !== 3) {
      update_option("_ACTIONWEAR_ONBOARDING", 0);
    }

    wp_remote_get(
      rest_url('/actionwear-api/v1/check_product_existance'),
      array(
        'timeout' => 1,
      )
    );

    $response->success(
      [
        "is_valid" => $isValidApikey,
        "success" => true
      ],
      "Chiave api valida"
    );
  } else {
    $response->fail("Chiave api non valida");
    return new WP_REST_Response($response->getResponse(), 401);
  }
  return $response->getResponse();
});
$route->registerRoute();
