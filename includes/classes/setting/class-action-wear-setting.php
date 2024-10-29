<?php

namespace AC_SYNC\Includes\Classes\Setting {
  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

  /**
   * Gestisce le impostazioni del modulo e alcuni metodi diretti, cambiabili dall'interfaccia "Avanzate" del modulo backend
   * 
   * Contiene metodo factory del costruttore e richiamabili con i metodi: get, set, toggle e validate delle singole impostazioni
   * 
   * Per registrare nuove impostazioni, definire il nome del tag dell'impostazione ed il nome di classe senza il prefisso Action_Wear_Setting dentro @see self::REGISTERED_SETTINGS
   * Attenersi all'impostazione delle classi simili, @see Action_Wear_Setting_Log_Writing
   */
  class Action_Wear_Setting implements Action_Wear_Setting_Entity
  {

    public $entity;

    const REGISTERED_SETTINGS = [
      "log_writing" => "Log_Writing",
      "debug_mode" => "Debug_Mode",
      "use_configurable" => "Use_Configurable",
      "reset_recharges" => "Reset_Recharges",
      "reset_settings" => "Reset_Settings",
      "resync_products" => "Resync_Products",
      "resync_images" => "Resync_Images",
      "supplier_availability" => "Supplier_Availability",
      "supplier_type" => "Supplier_Type",
      "toggle_price_sync" => "Toggle_Price_Sync",
      "toggle_recharge_tables" => "Toggle_Recharge_Tables",
      "toggle_images_customization" => "Toggle_Images_Customization",
      "cronjob_can_run" => "Cronjob_Can_Run",
    ];

    public static function getClassName($name)
    {
      return "\AC_SYNC\Includes\Classes\Setting\Action_Wear_Setting_" . $name;
    }

    public function __construct($entity)
    {
      $this->entity = $entity;
    }

    public function toggle()
    {
      return $this->entity->toggle();
    }

    public function setOnlyMissing($onlyMissing)
    {
      return $this->entity->setOnlyMissing($onlyMissing);
    }

    public function get()
    {
      return $this->entity->get();
    }

    public function set($value)
    {
      return $this->entity->set($value);
    }

    public function validate($v): bool
    {
      return $this->entity->validate($v);
    }

    /**
     * Restituisce tutte le impostazioni presenti dalle classe derivate che implementano Setting 
     *
     * Utile per conoscere tutto il set di impostazioni derivante dalle classi diverse.
     * Richiama se stesso instanziando le classi configurate dentro Setting e richiama i metodi get delle singole setting.
     *
     * @return array Un array chiave valore con nome => valore dell'impostazione, secondo il nome definito in self::REGISTERED_SETTINGS
     * 
     **/
    public static function getAllSettings()
    {
      $setting_values = [];
      foreach (self::REGISTERED_SETTINGS as $name => $setting_class) {
        $class_name = self::getClassName($setting_class);
        $s = new self(new $class_name);
        $setting_values[$name] = $s->get();
      }
      return $setting_values;
    }
  }
}
