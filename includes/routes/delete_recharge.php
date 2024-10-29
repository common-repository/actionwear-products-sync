<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge as Recharge;

$route = new Action_Wear_Api_Route("/delete_recharge");
$route->setMethods("POST");
$route->setCallback(function () {

  $response = new ApiResponse();
  $data = json_decode(file_get_contents("php://input"));

  $id = $data->id;
  if (Recharge::isGlobal($id)) {
    $response->fail("Non puoi eliminare la tabella globale");
    return new WP_REST_Response($response->getResponse(), 422);
  }

  Recharge::deleteById($id);
  $response->success("", "Eliminato con successo");
  return $response->getResponse();
});
$route->registerRoute();
