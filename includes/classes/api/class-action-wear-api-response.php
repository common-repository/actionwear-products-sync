<?php

namespace AC_SYNC\Includes\Classes\Api {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  class Action_Wear_Api_Response
  {

    private $success;
    private $message;
    private $data;
    private $xtime;

    public function __construct()
    {
      $this->xtime = microtime(true);
    }

    public function fail($message)
    {
      $this->success = false;
      $this->message = $message;
    }

    public function success($data = [], $message = "")
    {
      $this->success = true;
      $this->message = $message;
      $this->data = $data;
    }

    public function getSuccess()
    {
      return $this->success;
    }

    public function isSuccess()
    {
      return $this->getSuccess();
    }

    public function getResponse()
    {
      return [
        "success" => $this->success,
        "message" => $this->message,
        "data" => $this->data,
        "xtime" => microtime(true) - $this->xtime
      ];
    }
  }
}
