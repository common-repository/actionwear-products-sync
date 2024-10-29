<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * @deprecated
 * @see class-action-wear-setting-reset-settings
 */

use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;

$route = new Action_Wear_Api_Route("/reset_configuration");
$route->setMethods("GET");
$route->setCallback(function () {
  $response = new ApiResponse();

  update_option("_ACTIONWEAR_INITIAL_SYNC_PROGRESS", 0);
  update_option("_ACTIONWEAR_ONBOARDING", 0);
  update_option("_ACTIONWEAR_APIKEY", "");
  update_option("_ACTIONWEAR_BASE_URL", "https://action-wear.com/rest/it/V1");
  delete_option("_ACTIONWEAR_LIST_TYPE_SELECTED");
  delete_option("_ACTIONWEAR_SELECTED_TAXONOMIES");
  delete_option("_ACTIONWEAR_SUPPLIER_AVAILABILITY");
  delete_option("_ACTIONWEAR_IS_PRICE_SYNC_DISABLED");
  delete_option("_ACTIONWEAR_IMAGES_CUSTOMIZATION");
  delete_option("_ACTIONWEAR_DEBUG_MODE");
  delete_option("_ACTIONWEAR_USE_CONFIGURABLE");

  $response->success();

  return $response->getResponse();
});
$route->registerRoute();
