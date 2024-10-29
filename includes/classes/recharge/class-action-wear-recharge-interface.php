<?php

namespace AC_SYNC\Includes\Classes\Recharge{
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge_Type as RechargeType;

  interface Action_Wear_Recharge_Interface
  {

    public function __construct(RechargeType $type, $entity_id);

  }

}
