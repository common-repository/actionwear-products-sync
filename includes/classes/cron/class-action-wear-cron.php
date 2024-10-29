<?php

namespace AC_SYNC\Includes\Classes\Cron {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
  use AC_SYNC\Includes\Classes\Sync\Action_Wear_Sync as Sync;
  use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
  use AC_SYNC\Includes\Classes\Utils\Action_Wear_Utils as Utils;
  use AC_SYNC\Includes\Classes\Differential\Action_Wear_Differential as Differential;
  use AC_SYNC\Includes\Classes\Setting\Action_Wear_Setting_Debug_Mode as DebugMode;


  class Action_Wear_Cron
  {

    const DEBUG_MODE = true;
    const DISABLED = false;

    /**
     * Cronjob manager exec function
     *
     * All scheduled operations are grouped into this function
     *
     **/
    public static function exec($direct = false)
    {

      if (self::DISABLED) return;
      if (!Utils::cronjobCanRun() && !$direct) return;
      @ignore_user_abort(true);
      @set_time_limit(300);
      @ini_set('memory_limit', '512M');

      if (Sync::isLocked()) {
        $msg = "Cronjob locked, processo già in esecuzione";
        if (self::DEBUG_MODE) Log::write($msg, Log::INFO, Log::CONTEXT_CRONJOB);
        return $msg;
      }

      if (self::DEBUG_MODE) Log::write("Inizio esecuzione del cronjob", Log::INFO, Log::CONTEXT_CRONJOB);

      Sync::lock();

      $operations = Differential::getRegisteredOperations();
      foreach ($operations as $operation) {
        $differential = new Differential(new $operation);
        if (!$differential->isOperationToExecute()) continue;
        $differential->execute();
      }

      Sync::unlock();

      if (self::DEBUG_MODE) Log::write("Termino esecuzione del cronjob", Log::INFO, Log::CONTEXT_CRONJOB);
      Sync::updateLastSync(time());
    }
  }
}
