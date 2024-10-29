<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Category\Action_Wear_Category as Category;
use AC_SYNC\Includes\Classes\Category\Action_Wear_Category_Association as CategoryAssociation;


$route = new Action_Wear_Api_Route("/add_associations");
$route->setMethods("POST");
$route->setCallback(function () {
  $response = new ApiResponse();
  $categories = json_decode(file_get_contents("php://input"));
  $droppedActionwear = $categories->droppedActionwear;
  $droppedWc = $categories->droppedWc;
  $wc_id = $droppedWc->term_id ?? 0;
  if ($droppedWc === false) {
    $created = Category::wcCreateByCamacTree($droppedActionwear[0]->camac_name);
    $wc_id = $created["term_id"] ?? 0;
    if ($created === false) {
      $created = Category::wcGetByName($droppedActionwear[0]->camac_name);
      $wc_id = $created->term_id ?? 0;
      if ($created === false) {
        $response->fail("Impossibile creare o associare categoria WooCommerce automaticamente, creala prima su Woocommerce ed associala");
        return new WP_REST_Response($response->getResponse(), 422);
      }
    }
    $droppedWc = $created;
  }
  foreach ($droppedActionwear as $droppedAc) {
    $association = new CategoryAssociation($droppedAc->id_actionwear, $wc_id);
    $association->ignoreMe(false);
  }
  $response->success([]);
  return $response->getResponse();
});
$route->registerRoute();
