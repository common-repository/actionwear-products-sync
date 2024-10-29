<?php

namespace AC_SYNC\Includes\Classes\Setting {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  /**
   * Opzione che stabilisce se durante la creazione dei prodotti possono essere usate le immagini del modello o bisogna prendere la prima immagine del colore del prodotto semplice disponibile
   */
  class Action_Wear_Setting_Use_Configurable implements Action_Wear_Setting_Entity
  {

    public function toggle()
    {
      return null;
    }

    public function get()
    {
      return get_option("_ACTIONWEAR_USE_CONFIGURABLE", 0);
    }

    public function validate($v): bool
    {
      return is_bool($v);
    }

    public function set($value)
    {
      if (!$this->validate($value)) throw new \Exception("Only boolean values are accepted to this setting");
      update_option("_ACTIONWEAR_USE_CONFIGURABLE", (bool)$value === true ? 1 : 0);
      return $this->get();
    }
  }
}
