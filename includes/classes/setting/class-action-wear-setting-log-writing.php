<?php

namespace AC_SYNC\Includes\Classes\Setting {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  class Action_Wear_Setting_Log_Writing implements Action_Wear_Setting_Entity
  {

    public function toggle()
    {
      return $this->set(!$this->get());
    }

    public function get()
    {
      $path = dirname(__FILE__, 2) . "/logger/class-action-wear-log.php";
      $f = file_get_contents($path);
      return strpos($f, "const ENABLED = true;") !== false;
    }

    public function validate($v): bool
    {
      return is_bool($v);
    }

    public function set($value)
    {
      if (!$this->validate($value)) throw new \Exception("Only boolean values are accepted to this setting");
      $value = $value === true ? "true" : "false";
      $path = dirname(__FILE__, 2) . "/logger/class-action-wear-log.php";
      $f = file_get_contents($path);
      $f = preg_replace('/const ENABLED = (true|false);/i', "const ENABLED = $value;", $f);
      file_put_contents($path, $f);
      return $this->get();
    }
  }
}
