<?php

namespace AC_SYNC\Includes\Classes\Sql\Version {
    if (!defined('ABSPATH')) exit;

    abstract class Action_Wear_Sql_Version_Abstract implements Action_Wear_Sql_Version_Interface
    {
        public function updateVersionDb($version)
        {
            update_option('_ACTIONWEAR_DB_VERSION', $version);
        }
    }
}
