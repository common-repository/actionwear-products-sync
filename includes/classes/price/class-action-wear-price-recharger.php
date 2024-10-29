<?php

namespace AC_SYNC\Includes\Classes\Price {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge as Recharge;

  class Action_Wear_Price_Recharger
  {

    private $recharge_type;
    private $base_price;
    private $final_price;

    public function __construct($base_price)
    {
      $this->recharge_type = get_option("_ACTIONWEAR_RECHARGE_TYPE_SELECTED") === "ricarico-brand" ? "brand" : "category";
      $this->base_price = floatval($base_price);
    }

    /**
     * Get final price
     *
     * Get the final price based on recharges table price configuration 
     *
     * @param int $brand_id Brand ID of the product
     * @param array $cat_ids Array of categories ids which this product contain
     * @return float The final price
     **/
    public function getPrice(int $brand_id, array $cat_ids)
    {
      if (get_option("_ACTIONWEAR_ARE_RECHARGE_TABLES_DISABLED") == 1) {
        $this->final_price = $this->base_price;
        return $this->final_price;
      }

      $method = $this->recharge_type . "Rule";
      $method_param = $this->recharge_type === "brand" ? $brand_id : $cat_ids;
      $rule = $this->{$method}($method_param);
      if ($rule === false) {
        $rule = $this->generalRule();
      }
      $this->final_price = $this->base_price + floatval(number_format($this->base_price * ($rule / 100), 2));
      return $this->final_price;
    }

    public function generalRule()
    {
      $recharge = Recharge::general();
      $base_price = $this->base_price;
      $percent_increment = false;
      foreach ($recharge["details"] as $detail) {
        $price_from = floatval($detail->price_from);
        $price_to = floatval($detail->price_to);
        if (($base_price >= $price_from) && ($base_price <= $price_to)) $percent_increment = floatval($detail->percent);
      }
      if ($percent_increment === false) {
        $last_recharge = end($recharge["details"]);
        $percent_increment = $last_recharge->percent;
      }
      return floatval($percent_increment);
    }

    /**
     * Se presente una regola di brand di ricarico la applica, sennò restituisce false
     * 
     * @see getPrice() Viene utilizzata come tentativo di prelievo di regola brand
     * @return float|false
     **/
    public function brandRule(int $brand_id)
    {
      $recharge = Recharge::brand($brand_id);
      if ($recharge === false) return false;
      $base_price = $this->base_price;
      $percent_increment = false;
      foreach ($recharge["details"] as $detail) {
        $price_from = floatval($detail->price_from);
        $price_to = floatval($detail->price_to);
        if (($base_price >= $price_from) && ($base_price <= $price_to)) $percent_increment = floatval($detail->percent);
      }
      if ($percent_increment === false) {
        $last_recharge = end($recharge["details"]);
        $percent_increment = $last_recharge->percent;
      }
      return floatval($percent_increment);
    }

    public function categoryRule($ids)
    {
      $recharges = Recharge::category($ids);
      if ($recharges === false) return false;
      $base_price = $this->base_price;
      $percent_increment = [0];
      foreach ($recharges as $recharge) {
        foreach ($recharge["details"] as $detail) {
          $price_from = floatval($detail->price_from);
          $price_to = floatval($detail->price_to);
          if (($base_price >= $price_from) && ($base_price <= $price_to)) $percent_increment[] = floatval($detail->percent);
        }
      }
      return floatval(max($percent_increment));
    }
  }
}
