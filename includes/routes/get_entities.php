<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Category\Action_Wear_Category as Category;

$route = new Action_Wear_Api_Route("/get_entities");
$route->setMethods("GET");

$route->setCallback(function () {

  global $wpdb;
  $entity = isset($_GET["entity"]) ? sanitize_text_field($_GET["entity"]) : "all";

  // sanitize entity
  $entity = sanitize_text_field($entity);

  $response = new ApiResponse();
  $content = [];
  $valid_entities = ["categories", "brands", "all"];

  // valid entities validation
  if (!in_array($entity, $valid_entities)) {
    $response->fail("Invalid entity");
    return new WP_REST_Response($response->getResponse(), 422);
  }

  if ($entity === "categories" || $entity === "all") {
    $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}actionwear_categories WHERE id_actionwear NOT IN (SELECT recharge_entity_id FROM {$wpdb->prefix}actionwear_recharges WHERE recharge_entity_id <> 0)");
    $names = [];
    foreach ($categories as $index => $category) {
      if ($category->id <= 1) continue;
      $names[] = [
        "id_actionwear" => $category->id_actionwear,
        "name" => Category::getNamesTree($categories, $category->id_actionwear)
      ];
    }
    if ($entity === "all") $content["categories"] = $names;
    else $content = $names;
  }
  if ($entity === "brands" || $entity === "all") {
    $brands = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}actionwear_brands WHERE id_actionwear NOT IN (SELECT recharge_entity_id FROM {$wpdb->prefix}actionwear_recharges WHERE recharge_entity_id <> 0)");
    if ($entity === "all") $content["brands"] = $brands;
    else $content = $brands;
  }

  $response->success($content);

  return $response->getResponse();
});
$route->registerRoute();
