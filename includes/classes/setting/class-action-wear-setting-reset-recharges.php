<?php

namespace AC_SYNC\Includes\Classes\Setting {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge as Recharge;
  use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge_Detail as RechargeDetail;
  use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge_Type as RechargeType;

  class Action_Wear_Setting_Reset_Recharges implements Action_Wear_Setting_Entity
  {

    public function toggle()
    {
      return $this->set(!$this->get());
    }

    public function get()
    {
      return get_option("_ACTIONWEAR_LAST_RECHARGES_RESET");
    }

    public function validate($v): bool
    {
      return is_bool($v);
    }

    public function set($value)
    {
      global $wpdb;
      if (!$this->validate($value)) throw new \Exception("Only boolean values are accepted to this setting");
      $wpdb->query("DELETE FROM {$wpdb->prefix}actionwear_recharges_details;");
      $wpdb->query("DELETE FROM {$wpdb->prefix}actionwear_recharges;");
      $type = new RechargeType("global");
      $recharge = new Recharge($type, 0);
      $price = [
        "price_from" => 0.01,
        "price_to" => 5
      ];
      $quantity = [
        "quantity_from" => 1,
        "quantity_to" => 99999
      ];
      $percent = 30;
      $detail = new RechargeDetail($price, $quantity, $percent);
      $detail->addToRecharge($recharge);
      update_option("_ACTIONWEAR_LAST_RECHARGES_RESET", time());
      return true;
    }
  }
}
