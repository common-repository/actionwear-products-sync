<?php

/**
 * Actionwear
 *
 * @package       actionwear  
 * @author        DGCAL SRL
 * @version 2.3.0
 *
 * @wordpress-plugin
 * Plugin Name:   Actionwear products sync
 * Plugin URI:    https://action-wear.com
 * Description:   Synchronize products from actionwear.com to your e-shop in a easy way
 * Version: 2.3.0
 * Author:        DGCAL SRL
 * Author URI:    https://www.dgcal.it/
 * Text Domain:   actionwear
 * Domain Path:   /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

// enable display errors in localhost env
$server_name = $_SERVER['SERVER_NAME'] ?? '';
if ($server_name == 'localhost') {
  ini_set('display_errors', 1);
}

$actionwear_plugin_path = plugin_dir_path(__FILE__);

function AC_CORE_auto_loader($class_name)
{
  if (!is_int(strpos($class_name, 'AC_SYNC'))) return;
  $class_name = str_replace('AC_SYNC\\', '', $class_name);
  $class_name = str_replace('\\', '/', strtolower($class_name)) . '.php';
  $pos =  strrpos($class_name, '/');
  $file_name = is_int($pos) ? substr($class_name, $pos + 1) : $class_name;
  $path = str_replace($file_name, '', $class_name);
  $new_file_name = 'class-' . str_replace('_', '-', $file_name);
  $file_path =  plugin_dir_path(__FILE__) . str_replace('\\', DIRECTORY_SEPARATOR, $path . strtolower($new_file_name));
  if (file_exists($file_path)) require_once($file_path);
}

spl_autoload_register('AC_CORE_auto_loader');

$data = get_file_data(__FILE__, array('Version' => 'Version'), false);
$core = new AC_SYNC\Action_Wear_Core(__FILE__);
$core->initialize($data["Version"]);
