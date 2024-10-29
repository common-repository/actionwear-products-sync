<?php

namespace AC_SYNC\Includes\Classes\Setting {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  /**
   * Il metodo set di questo setting deve reimpostare il modulo alla fase "iniziale" di installazione
   *
   **/
  class Action_Wear_Setting_Reset_Settings implements Action_Wear_Setting_Entity
  {

    public function toggle()
    {
      return $this->set(!$this->get());
    }

    public function get()
    {
      return get_option("_ACTIONWEAR_LAST_RESET_SETTINGS");
    }

    public function validate($v): bool
    {
      return is_bool($v);
    }

    public function set($value)
    {
      if (!$this->validate($value)) throw new \Exception("Only boolean values are accepted to this setting");
      update_option("_ACTIONWEAR_INITIAL_SYNC_PROGRESS", 0);
      update_option("_ACTIONWEAR_ONBOARDING", 0);
      update_option("_ACTIONWEAR_USE_CONFIGURABLE", 0);
      update_option("_ACTIONWEAR_SUPPLIER_AVAILABILITY", 0);
      update_option("_ACTIONWEAR_APIKEY", "");
      update_option("_ACTIONWEAR_LAST_RESET_SETTINGS", time());
      update_option("_ACTIONWEAR_LAST_RESYNCED_PRODUCTS", time());
      update_option("_ACTIONWEAR_PRODUCTS_LANG", "it");
      delete_option("_ACTIONWEAR_LIST_TYPE_SELECTED");
      delete_option("_ACTIONWEAR_DEBUG_MODE");
      delete_option("_ACTIONWEAR_RECHARGE_TYPE_SELECTED");
      delete_option("_ACTIONWEAR_API_ERROR");
      delete_option("_ACTIONWEAR_LOCK");
      delete_option("_ACTIONWEAR_SELECTED_TAXONOMIES");
      delete_option("_ACTIONWEAR_IMAGES_CUSTOMIZATION");
      delete_option("_ACTIONWEAR_CRONJOB_FROZEN");
      return true;
    }
  }
}
