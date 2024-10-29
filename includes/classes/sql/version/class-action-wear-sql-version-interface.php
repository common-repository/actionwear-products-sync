<?php

namespace AC_SYNC\Includes\Classes\Sql\Version {
    if (!defined('ABSPATH')) exit; // Exit if accessed directly
    interface Action_Wear_Sql_Version_Interface
    {
        public static function versioningScript();
    }
}
