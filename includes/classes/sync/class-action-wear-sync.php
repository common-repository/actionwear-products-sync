<?php

namespace AC_SYNC\Includes\Classes\Sync {
  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Setting\Action_Wear_Setting_Resync_Products as Resync_Products;

  class Action_Wear_Sync
  {

    const INITIAL = 0;
    const BRANDS_GENERATED = 24;
    const CATEGORIES_GENERATED = 39;
    const PRODUCTS_GENERATED = 76;
    const IMAGES_GENERATED = 100;

    public static function getProgress()
    {
      return (int) get_option("_ACTIONWEAR_INITIAL_SYNC_PROGRESS");
    }

    public static function setProgress($p)
    {
      update_option("_ACTIONWEAR_INITIAL_SYNC_PROGRESS", $p);
    }

    public static function lock()
    {
      update_option("_ACTIONWEAR_LOCK", time());
    }

    /**
     * Lock status
     *
     * Return the lock status or unlock it if stuck ( 5 minutes or more locked )
     *
     **/
    public static function isLocked()
    {
      $lock = (int) get_option("_ACTIONWEAR_LOCK", 0);
      if (time() - $lock > 300) {
        delete_option("_ACTIONWEAR_LOCK");
        return false;
      }
      return $lock > 0;
    }

    public static function unlock()
    {
      delete_option("_ACTIONWEAR_LOCK");
    }

    public static function getNbProductsToProcess()
    {
      global $wpdb;
      return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}actionwear_products WHERE wc_to_create = 1 AND wc_created = 0");
    }

    public static function getRealWoocommerceProducts()
    {
      global $wpdb;
      $real_skus = [];
      $products = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "actionwear_products");
      $skus = $wpdb->get_col("SELECT meta_value FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_sku'");
      foreach ($products as $product) {
        if (in_array($product->sku, $skus)) {
          $real_skus[] = $product->sku;
        }
      }
      return $real_skus;
    }

    public static function getNbProductsProcessed()
    {
      global $wpdb;
      $count = 0;
      $products = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "actionwear_products");
      $skus = $wpdb->get_col("SELECT meta_value FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_sku'");
      foreach ($products as $product) {
        if (in_array($product->sku, $skus)) {
          $count++;
        }
      }
      return $count;
    }

    public static function getNbProductsTotal()
    {
      global $wpdb;
      return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}actionwear_products WHERE wc_to_create = 1");
    }

    public static function updateLastSync($time)
    {
      update_option("_ACTIONWEAR_LAST_SYNC", $time);
    }

    public static function getLastSync()
    {
      $last = get_option("_ACTIONWEAR_LAST_SYNC");
      return $last === false ? "MAI" : date("d/m/Y H:i:s", $last);
    }

    public static function isProcessingAnymore()
    {
      return self::getNbProductsToProcess() !== 0;
    }
    public static function getResyncStatusByDates(array $dates)
    {
      $resync_products = new Resync_Products();
      $last_resync = $resync_products->get();
      foreach ($dates as $date) {
        $time = strtotime($date);
        if ($time > $last_resync)
          return true;
      }
      return false;
    }
    public static function getActionWearOrders()
    {
      global $wpdb;
      $query = "SELECT COUNT(DISTINCT(woi.order_id)) FROM " . $wpdb->prefix . "woocommerce_order_itemmeta AS woii LEFT JOIN " . $wpdb->prefix . "woocommerce_order_items AS woi ON woi.order_item_id = woii.order_item_id LEFT JOIN " . $wpdb->prefix . "postmeta AS pm ON pm.post_id = woii.meta_value WHERE woii.meta_key = '_product_id' and pm.meta_key = 'camac_cdn_urls'";
      return (int) $wpdb->get_var($query);
    }
  }
}
