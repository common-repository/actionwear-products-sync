<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;

$route = new Action_Wear_Api_Route("/delete_products");
$route->setMethods("POST");

/**
 * Method to delete Woo Product
 * 
 * @param int $id the product ID.
 * @param bool $force true to permanently delete product, false to move to trash.
 * @return \WP_Error|boolean
 */
function wh_deleteProduct($id, $force = FALSE)
{
  $product = wc_get_product($id);

  if (empty($product))
    return new WP_Error(999, sprintf(__('No %s is associated with #%d', 'woocommerce'), 'product', $id));

  // If we're forcing, then delete permanently.
  if ($force) {
    if ($product->is_type('variable')) {
      foreach ($product->get_children() as $child_id) {
        $child = wc_get_product($child_id);
        $child->delete(true);
      }
    } elseif ($product->is_type('grouped')) {
      foreach ($product->get_children() as $child_id) {
        $child = wc_get_product($child_id);
        $child->set_parent_id(0);
        $child->save();
      }
    }

    $product->delete(true);
    $result = $product->get_id() > 0 ? false : true;
  } else {
    $product->delete();
    $result = 'trash' === $product->get_status();
  }

  if (!$result) {
    return new WP_Error(999, sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'));
  }

  // Delete parent product transients.
  if ($parent_id = wp_get_post_parent_id($id)) {
    wc_delete_product_transients($parent_id);
  }
  return true;
}

$route->setCallback(function () {
  global $wpdb;

  $response = new ApiResponse();

  $payload = json_decode(file_get_contents("php://input"));
  $ids = $payload->ids;

  if (empty($ids)) {
    $response->fail("No ids provided");
    return $response->getResponse();
  }

  $data = [
    "success" => true
  ];

  // delete woocommerce products by ids
  foreach ($ids as $id) {
    wh_deleteProduct($id, true);
  }

  $response->success($data);
  return $response->getResponse();
});
$route->registerRoute();
