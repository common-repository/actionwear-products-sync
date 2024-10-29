<?php

namespace AC_SYNC\Includes\Classes\Setting {
    if (!defined('ABSPATH')) exit; // Exit if accessed directly
    class Action_Wear_Setting_Toggle_Recharge_Tables implements Action_Wear_Setting_Entity
    {

        public function toggle()
        {
            return null;
        }

        public function get()
        {
            return get_option("_ACTIONWEAR_ARE_RECHARGE_TABLES_DISABLED", 0);
        }

        public function validate($v): bool
        {
            return is_bool($v);
        }

        public function set($value)
        {
            if (!$this->validate($value)) throw new \Exception("Only boolean values are accepted to this setting");
            update_option("_ACTIONWEAR_ARE_RECHARGE_TABLES_DISABLED", (bool)$value === true ? 1 : 0);
            return $this->get();
        }
    }
}
