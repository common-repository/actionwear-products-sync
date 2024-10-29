<?php

namespace AC_SYNC\Includes\Classes\Setting {
  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
  ignore_user_abort(true);

  use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
  use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;

  /**
   * Elimina e re-sincronizza tutti i prodotti CAMAC
   *
   **/
  class Action_Wear_Setting_Resync_Products implements Action_Wear_Setting_Entity
  {

    private $onlyMissing = false;

    public function setOnlyMissing($onlyMissing)
    {
      $this->onlyMissing = $onlyMissing;
    }
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

      if (!$this->onlyMissing)
        $wpdb->query("UPDATE {$wpdb->prefix}actionwear_products SET wc_created = 0;");

      $query = "SELECT sku FROM " . $wpdb->prefix . "actionwear_products WHERE wc_to_create = 1 ";
      if ($this->onlyMissing) {
        $query .= "AND wc_created = 0";
      }
      $skus = $wpdb->get_col($query);
      foreach ($skus as $sku) {
        \AC_SYNC\Action_Wear_Core::$importProcess->push_to_queue($sku);
      }
      \AC_SYNC\Action_Wear_Core::$importProcess->save();

      update_option("_ACTIONWEAR_LAST_RESYNCED_PRODUCTS", time());
      return true;
    }
  }
}