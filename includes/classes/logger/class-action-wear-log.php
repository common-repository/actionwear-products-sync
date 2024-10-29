<?php

namespace AC_SYNC\Includes\Classes\Logger {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  class Action_Wear_Log
  {

    const CRITICAL = 0;
    const ERROR = 1;
    const WARNING = 2;
    const INFO = 3;

    const CONTEXT_GENERAL = "GENERAL";
    const CONTEXT_PRODUCT_CREATION = "PRODUCT_CREATION";
    const CONTEXT_PRODUCT_UPDATE = "PRODUCT_UPDATE";
    const CONTEXT_CRONJOB = "CRONJOB";
    const CONTEXT_CAMAC_API = "CAMAC_API";

    const ENABLED = true;

    /**
     * Scrive un log
     *
     * Scrive un log dentro la tabella actionwear_log
     *
     * @param string $msg Il messaggio da scrivere
     * @param self::CRITICAL|self::ERROR|self::WARNING|self::INFO|int $gravity Un numero che indica la gravità del log, disponibili costanti di classe
     * @param self::CONTEXT_GENERAL|self::CONTEXT_PRODUCT_CREATION|self::CONTEXT_PRODUCT_UPDATE|self::CONTEXT_CRONJOB|string $context Una stringa che identifica il contesto, preferibile usare le costanti di classi ma si possono mettere anche valori custom
     * 
     * @return int Id della riga inserita
     **/
    public static function write(string $msg, $gravity = self::CRITICAL, $context = self::CONTEXT_GENERAL, $line = null, $file = null)
    {
      if (!self::ENABLED) return;
      global $wpdb;
      if ($line !== null) $msg .= " in line $line";
      if ($file !== null) $msg .= " on file $file";
      $wpdb->insert($wpdb->prefix . "actionwear_log", [
        "message" => $msg,
        "gravity" => $gravity,
        "context" => $context
      ]);
      return $wpdb->insert_id;
    }

    public static function getLogs(int $limit = 10000)
    {
      global $wpdb;
      $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}actionwear_log ORDER BY id DESC LIMIT $limit");
      return $logs;
    }

    public static function truncateLogs()
    {
      global $wpdb;
      return $wpdb->query("DELETE FROM {$wpdb->prefix}actionwear_log");
    }
  }
}
