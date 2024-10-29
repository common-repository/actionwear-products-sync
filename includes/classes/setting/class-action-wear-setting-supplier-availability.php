<?php

namespace AC_SYNC\Includes\Classes\Setting {
    if (!defined('ABSPATH'))
        exit; // Exit if accessed directly
    /**
     * Opzione che stabilisce se mostrare o meno la disponibilità del fornitore.
     */
    class Action_Wear_Setting_Supplier_Availability implements Action_Wear_Setting_Entity
    {

        public function toggle()
        {
            return null;
        }

        public function get()
        {
            return get_option("_ACTIONWEAR_SUPPLIER_AVAILABILITY", 0);
        }

        public function validate($v): bool
        {
            return is_bool($v);
        }

        public function set($value)
        {
            if (!$this->validate($value))
                throw new \Exception("Only boolean values are accepted to this setting");
            update_option("_ACTIONWEAR_SUPPLIER_AVAILABILITY", (bool) $value === true ? 1 : 0);
            if ((bool) $value === false) {
                delete_option("_ACTIONWEAR_SUPPLIER_TYPE");
            }
            ;
            return $this->get();
        }
    }
}
