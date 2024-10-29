<?php

namespace AC_SYNC\Includes\Classes\Api {
  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Api\Action_Wear_Api;

  class Action_Wear_Api_Route
  {

    public $name;
    public $methods;
    public $permission_callback;
    public $callback;
    public $logged = false;

    const START_API_POINT = "actionwear-api/v1";

    public function __construct($name)
    {
      $this->name = $name;
    }

    public function setMethods($methods)
    {
      $this->methods = $methods;
      return $this;
    }

    public function setCallback($cb)
    {
      $this->callback = $cb;
      return $this;
    }

    public function protectedMethod()
    {
      $this->logged = is_user_logged_in();
      if (!Action_Wear_Api::PROTECT_API)
        $this->permission_callback = "__return_true";
      else
        $this->permission_callback = function (\WP_REST_Request $request) {
          $route = $request->get_route();
          return $this->logged || $route === "/actionwear-api/v1/check_product_existance";
        };
      return $this;
    }

    public function registerRoute()
    {
      $this->protectedMethod();
      register_rest_route(
        self::START_API_POINT,
        $this->name,
        [
          'methods' => $this->methods,
          'permission_callback' => $this->permission_callback,
          'callback' => function (\WP_REST_Request $request) {
            return call_user_func($this->callback);
          }
        ]
      );
    }
  }
}
