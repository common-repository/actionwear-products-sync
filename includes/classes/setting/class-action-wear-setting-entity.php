<?php

namespace AC_SYNC\Includes\Classes\Setting {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  interface Action_Wear_Setting_Entity
  {
    public function toggle();
    public function get();
    public function set($value);
    public function validate($v): bool;
  }
}
