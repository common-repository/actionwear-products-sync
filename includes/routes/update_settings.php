<?php
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Setting\Action_Wear_Setting as Setting;

$route = new Action_Wear_Api_Route("/update_settings");
$route->setMethods("POST");
$route->setCallback(function () {
  $response = new ApiResponse();

  $data = json_decode(file_get_contents("php://input"));
  $raw_settings = $data->settings;
  $setting_keys = array_keys(Setting::REGISTERED_SETTINGS);
  $onlyMissing = false;
  if (isset($raw_settings->onlyMissing)) {
    $onlyMissing = $raw_settings->onlyMissing;
    unset($raw_settings->onlyMissing);
  }
  foreach ($raw_settings as $raw_setting => $raw_value) {
    if (in_array($raw_setting, $setting_keys)) {
      $class_name = Setting::getClassName($raw_setting);
      $setting = new Setting(new $class_name);
      if ($onlyMissing === true) {
        $setting->setOnlyMissing($onlyMissing);
      }
      try {
        $setting->set($raw_value);
      } catch (\Throwable $e) {
        $response->fail($e->getMessage());
        return new WP_REST_Response($response->getResponse(), 422);
      }
    }
  }

  $response->success([], "Operazione completata");
  return $response->getResponse();
});
$route->registerRoute();
