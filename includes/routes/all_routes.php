<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly 
foreach (glob(dirname(__FILE__) . DIRECTORY_SEPARATOR . "*.php") as $f) {
  if (strpos($f, "all_routes") === false) include $f;
}
