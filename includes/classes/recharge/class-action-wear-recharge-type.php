<?php

namespace AC_SYNC\Includes\Classes\Recharge {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  /**
   * Define a Recharge Type to be used to constraint Recharge Types used in class constructors
   */

  class Action_Wear_Recharge_Type
  {

    const ALLOWED = ["global", "brand", "category"];
    private $type;

    public function __construct($type)
    {
      if (!in_array($type, self::ALLOWED))
        throw new \Exception("Allowed Recharge types are: global, brand or category. Got: $type");
      $this->type = $type;
      return $type;
    }

    public function getType()
    {
      return $this->type;
    }

    public function isBrand()
    {
      return $this->type === "brand";
    }

    public function isCategory()
    {
      return $this->type === "category";
    }

    public function isGlobal()
    {
      return $this->type === "global";
    }
  }
}
