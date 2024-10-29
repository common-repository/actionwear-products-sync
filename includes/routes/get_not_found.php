<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;

$route = new Action_Wear_Api_Route("/get_not_found");
$route->setMethods("GET");
$route->setCallback(function () {
  global $wpdb;
  $response = new ApiResponse();

  // get array of actionwear skus
  $actionwear_skus = $wpdb->get_results("SELECT sku FROM {$wpdb->prefix}actionwear_products", ARRAY_A);
  $actionwear_skus = array_column($actionwear_skus, 'sku');

  // get array of woocommerce skus
  $woocommerce_skus = $wpdb->get_results("SELECT pm_sku.meta_value AS sku FROM {$wpdb->prefix}postmeta AS pm_sku INNER JOIN {$wpdb->prefix}postmeta AS pm_cdn ON pm_sku.post_id = pm_cdn.post_id WHERE pm_sku.meta_key = '_sku' AND pm_cdn.meta_key = 'camac_cdn_urls'", ARRAY_A);
  $woocommerce_skus = array_column($woocommerce_skus, 'sku');

  // take only the first exploded part of sku
  $woocommerce_skus = array_map(function ($sku) {
    return explode("-", $sku)[0];
  }, $woocommerce_skus);

  // remove duplicated from woocommerce skus
  $woocommerce_skus = array_unique($woocommerce_skus);

  // get not found skus
  $not_found = array_diff($woocommerce_skus, $actionwear_skus);

  $products = [];

  // get products from woocommerce
  foreach ($not_found as $sku) {
    $product = wc_get_product_id_by_sku($sku);
    $product = wc_get_product($product);
    if (!$product)
      continue;
    $products[] = [
      "sku" => $sku,
      "id" => $product->get_id(),
      "image" => $product->get_image(),
      "created_at" => $product->get_date_created(),
    ];
  }

  $data = [
    "not_found" => $not_found,
    "products" => $products,
  ];

  $response->success($data);
  return $response->getResponse();
});
$route->registerRoute();
