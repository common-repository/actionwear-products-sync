<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Product\Action_Wear_Product;

$route = new Action_Wear_Api_Route("/update_onboarding");
$route->setMethods("POST");
$route->setCallback(function () {

  $response = new ApiResponse();
  $incoming_data = json_decode(file_get_contents("php://input"));
  $new_onboarding = $incoming_data->new_onboarding;
  $valid_onboarding = [0, 1, 2, 3, 4];
  $selectedProducts = $incoming_data->selectedProducts ?? NULL;
  $number = (int)$new_onboarding;
  if (!empty($selectedProducts)) {
    if ($selectedProducts->all === true) {
      $selection_label = "ALL";
    }
    if (count($selectedProducts->category_ids) > 0) {
      $selection_label = "CATEGORIES";
    }
    if (count($selectedProducts->ids) > 0) {
      $selection_label = "PRODUCTS";
    }
    $setProductsOp = Action_Wear_Product::setProductsToImportBySelection($selection_label, $selectedProducts);
    if (!$setProductsOp) {
      $response->fail("Errore durante l'import dei prodotti");
      return new WP_REST_Response($response->getResponse(), 422);
    }
  }

  if (in_array($number, $valid_onboarding)) {
    update_option("_ACTIONWEAR_ONBOARDING", $number);
  }

  $response->success(
    [
      "new_onboarding" => $number,
      "success" => true
    ],
    "Modifiche salvate"
  );
  return $response->getResponse();
});
$route->registerRoute();
