<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Category\Action_Wear_Category as Category;
use AC_SYNC\Includes\Classes\Category\Action_Wear_Category_Association as CategoryAssociation;



$route = new Action_Wear_Api_Route("/get_associations");
$route->setMethods("GET");
$route->setCallback(function () {
  $response = new ApiResponse();
  $ac_categories = Category::getAllCategoriesToAssociate();
  $wc_categories = Category::getAllWcCategories(Category::ORDER_NAMES_TREE);
  $associations = CategoryAssociation::getAllAssociations();

  $response->success([
    "ac_categories" => Category::sortAlphabetically($ac_categories),
    "wc_categories" => Category::sortAlphabetically($wc_categories),
    "associations" => $associations
  ]);
  return $response->getResponse();
});
$route->registerRoute();
