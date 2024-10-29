<?php

namespace AC_SYNC\Includes\Classes\Context {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge as Recharge;

  class Action_Wear_Context
  {

    private static $instance = null;
    public $recharges;
    public $configuration;

    public static function getContext()
    {
      if (!self::$instance) self::$instance = new self();
      return self::$instance;
    }

    public function loadRecharges()
    {
      if (!$this->recharges) {
        $this->recharges = Recharge::getAll();
      }
    }

    private function __construct()
    {
    }
  }
}
