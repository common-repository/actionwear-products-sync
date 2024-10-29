<?php

namespace AC_SYNC\Includes\Classes\Setting {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  class Action_Wear_Setting_Debug_Mode implements Action_Wear_Setting_Entity
  {

    public function toggle()
    {
      return $this->set(!$this->get());
    }

    public function get()
    {
      return get_option("_ACTIONWEAR_DEBUG_MODE", 0);
    }

    public static function getDebugMode()
    {
      $debug_mode = get_option("_ACTIONWEAR_DEBUG_MODE", 0);
      return $debug_mode == 0 ? false : true;
    }

    public function validate($v): bool
    {
      return is_bool($v);
    }

    public function set($value)
    {
      if (!$this->validate($value)) throw new \Exception("Only boolean values are accepted to this setting");
      update_option("_ACTIONWEAR_DEBUG_MODE", (bool)$value === true ? 1 : 0);
      return $this->get();
    }
  }
}
