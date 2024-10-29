<?php

namespace AC_SYNC\Includes\Classes\Setting {
    if (!defined('ABSPATH')) exit; // Exit if accessed directly
    /**
     * Opzione che stabilisce se mostrare o meno la disponibilità del fornitore.
     */
    class Action_Wear_Setting_Supplier_Type implements Action_Wear_Setting_Entity
    {

        public function toggle()
        {
            return null;
        }

        public function get()
        {
            return get_option("_ACTIONWEAR_SUPPLIER_TYPE", "simple");
        }

        public function validate($v): bool
        {
            return is_string($v);
        }

        public function set($value)
        {
            if (!$this->validate($value)) throw new \Exception("Only string values are accepted to this setting");
            update_option("_ACTIONWEAR_SUPPLIER_TYPE", (string)$value);
            return $this->get();
        }
    }
}
