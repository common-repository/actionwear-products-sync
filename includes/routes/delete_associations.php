<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Category\Action_Wear_Category_Association as CategoryAssociation;


$route = new Action_Wear_Api_Route("/delete_associations");
$route->setMethods("POST");
$route->setCallback(function () {
  $response = new ApiResponse();
  $categories = json_decode(file_get_contents("php://input"));
  $id = $categories->id;
  CategoryAssociation::deleteByWcId($id);
  $response->success([]);
  return $response->getResponse();
});
$route->registerRoute();
