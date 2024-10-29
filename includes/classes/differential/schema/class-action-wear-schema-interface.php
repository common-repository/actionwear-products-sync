<?php

namespace AC_SYNC\Includes\Classes\Differential\Schema;

if (!defined('ABSPATH')) exit; // Exit if accessed directly
interface Action_Wear_Schema_Interface
{

    public function getSchema(): array;
}
