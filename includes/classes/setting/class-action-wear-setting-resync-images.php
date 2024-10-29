<?php

namespace AC_SYNC\Includes\Classes\Setting {
  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
  ignore_user_abort(true);

  use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
  use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
  use AC_SYNC\Includes\Classes\Sync\Action_Wear_Sync as Sync;


  /**
   * Elimina e re-sincronizza tutti i prodotti CAMAC
   *
   **/
  class Action_Wear_Setting_Resync_Images implements Action_Wear_Setting_Entity
  {

    public function toggle()
    {
      return $this->set(!$this->get());
    }

    public function get()
    {
      return get_option("_ACTIONWEAR_LAST_RESYNCED_PRODUCTS", 0);
    }

    public function validate($v): bool
    {
      return is_bool($v);
    }

    public function set($value)
    {
      global $wpdb;
      if (!$this->validate($value))
        throw new \Exception("Only boolean values are accepted to this setting");
      $wpdb->query("TRUNCATE " . $wpdb->prefix . "actionwear_images");
      try {
        Product::generateImages();
      } catch (\Exception $e) {
        Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_PRODUCT_CREATION);
      }

      $skus = Sync::getRealWoocommerceProducts();
      foreach ($skus as $sku) {
        \AC_SYNC\Action_Wear_Core::$importImages->push_to_queue($sku);
      }
      \AC_SYNC\Action_Wear_Core::$importImages->save();

      return true;
    }
  }
}