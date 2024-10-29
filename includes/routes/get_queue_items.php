<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Sync\Action_Wear_Queue as Queue;

$route = new Action_Wear_Api_Route("/get_queue_items");
$route->setMethods("GET");
$route->setCallback(function () {
  $response = new ApiResponse();

  $data = ["queue" => Queue::getQueue()];

  $response->success($data);
  return $response->getResponse();
});
$route->registerRoute();
