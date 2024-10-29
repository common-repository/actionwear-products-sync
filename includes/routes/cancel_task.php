<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Sync\Action_Wear_Queue as Queue;

$route = new Action_Wear_Api_Route("/cancel_task");
$route->setMethods("POST");
$route->setCallback(function () {
  $response = new ApiResponse();

  $data = json_decode(file_get_contents("php://input"));
  $name = $data->name;

  Queue::deleteTask($name);

  $response->success(true);
  return $response->getResponse();
});
$route->registerRoute();
