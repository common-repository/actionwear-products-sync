<?php

namespace AC_SYNC\Includes\Classes\Sync;

if (!defined('ABSPATH'))
  exit; // Exit if accessed directly

class Action_Wear_Queue
{
  public static function getQueue()
  {
    global $wpdb;
    $items = [];
    $queue = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE '%actionwear%batch%'");
    foreach ($queue as $item) {
      if (strpos($item->option_name, "delete") !== false)
        continue;
      $name = explode("_", $item->option_name);
      $option_value = unserialize($item->option_value);
      // transform all values that are not string in string
      foreach ($option_value as $key => $value) {
        if (isset($value->sku)) {
          $option_value[$key] = $value->sku;
        }
      }
      $item->option_value = $option_value;
      $item->hash = end($name);
      $item->type = str_replace("actionwear_", "", str_replace("_" . $item->hash, "", str_replace("_batch", "", $item->option_name)));
      $item->cancel = (bool) get_option("delete_" . $item->option_name);
      $items[] = $item;
    }
    return $items;
  }

  public static function deleteTask($name)
  {
    add_option("delete_" . $name, "1");
  }

}