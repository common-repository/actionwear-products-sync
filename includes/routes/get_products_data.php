<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;

$route = new Action_Wear_Api_Route("/get_products_data");
$route->setMethods("GET");

function addChildrens(&$tree, $cats, $ida)
{
  foreach ($cats as $cat) {
    if ($ida == $cat->parent_id_actionwear) {
      $cat->children = [];
      $cat->value = $cat->id_actionwear;
      $cat->label = $cat->name . " (" . $cat->product_count . ")";
      $tree[] = $cat;
      $this_tree = end($tree);
      addChildrens($this_tree->children, $cats, $cat->id_actionwear);
    }
  }
}

$route->setCallback(function () {

  global $wpdb;

  $response = new ApiResponse();

  $products = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "actionwear_products");
  $skus = $wpdb->get_col("SELECT meta_value FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_sku'");
  // add wc_exist to products array
  foreach ($products as $key => $product) {
    $products[$key]->wc_exist = in_array($product->sku, $skus);
  }
  $categories = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "actionwear_categories");

  // rebuild categories tree from db raw data
  $tree = [];
  $categories[0]->children = [];
  $tree = $categories[0];
  $tree->value = $tree->id_actionwear;
  $tree->label = $tree->name;
  addChildrens($tree->children, $categories, $tree->id_actionwear);

  $brands = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "actionwear_brands");

  $response->success([
    "products" => $products,
    "categories_tree" => $tree,
    "brands" => $brands,
  ]);
  return $response->getResponse();
});
$route->registerRoute();
