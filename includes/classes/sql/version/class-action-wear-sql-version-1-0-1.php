<?php

namespace AC_SYNC\Includes\Classes\Sql\Version {
    if (!defined('ABSPATH')) exit; // Exit if accessed directly
    class Action_Wear_Sql_Version_1_0_1 extends Action_Wear_Sql_Version_Abstract implements Action_Wear_Sql_Version_Interface
    {
        public static function versioningScript()
        {
            update_option('_ACTIONWEAR_DB_UPGRADE_TEST_SUCCESS', 'OK');
        }
    }
}
