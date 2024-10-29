<?php

namespace AC_SYNC\Includes\Classes\Recharge {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge_Interface as RechargeInterface;
  use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge_Type as RechargeType;
  use AC_SYNC\Includes\Classes\Recharge\Action_Wear_Recharge_Detail as RechargeDetail;
  use AC_SYNC\Includes\Classes\Context\Action_Wear_Context as Context;

  class Action_Wear_Recharge implements RechargeInterface
  {

    private $id;
    private $recharge_type;
    private $recharge_entity_id;
    private $name;
    private $details = [];

    public static function isGlobal($id)
    {
      global $wpdb;
      $id = (int)$id;
      $entity_id = $wpdb->get_var("SELECT recharge_entity_id FROM {$wpdb->prefix}actionwear_recharges WHERE id = $id");
      return (int)$entity_id === 0;
    }

    public static function getListTypeConfigured()
    {
      return get_option("_ACTIONWEAR_LIST_TYPE_SELECTED", "");
    }

    public static function getRechargeTypeConfigured()
    {
      return get_option("_ACTIONWEAR_RECHARGE_TYPE_SELECTED", "");
    }

    public static function hasConfiguredListType()
    {
      return get_option("_ACTIONWEAR_LIST_TYPE_SELECTED", "") !== "";
    }

    public static function hasConfiguredRechargeType()
    {
      return get_option("_ACTIONWEAR_RECHARGE_TYPE_SELECTED", "") !== "";
    }

    private function getNameByType(RechargeType $type, $entity_id)
    {
      global $wpdb;
      $name = "Ricarico globale";

      if ($type->isBrand()) {
        $n = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}actionwear_brands WHERE id_actionwear = $entity_id");
        $name = "Ricarico brand: $n";
      }

      if ($type->isCategory()) {
        $n = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}actionwear_categories WHERE id_actionwear = $entity_id");
        $name = "Ricarico categoria: $n";
      }

      return $name;
    }

    public static function deleteById($id)
    {
      global $wpdb;
      $id = (int)$id;
      $childrens = $wpdb->delete("{$wpdb->prefix}actionwear_recharges_details", ["id_recharge" => $id]);
      $parent = $wpdb->delete("{$wpdb->prefix}actionwear_recharges", ["id" => $id]);
      return $childrens !== false && $parent !== false;
    }

    public function __construct(RechargeType $type, $entity_id, $recharge_to_load = null)
    {

      global $wpdb;

      if ($type->isGlobal() && $recharge_to_load === null) {
        $globalAlreadyExist = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}actionwear_recharges WHERE recharge_type = 'global'") !== 0;
        if ($globalAlreadyExist) {
          throw new \Exception("Esiste già una tabella globale");
        }
      }

      if (self::entityIdExist($entity_id) && $recharge_to_load === null) throw new \Exception("Esiste già questa tabella");

      $name = $this->getNameByType($type, $entity_id);

      $this->recharge_type = $type->getType();
      $this->recharge_entity_id = $entity_id;
      $this->name = $name;

      if ($recharge_to_load !== null && is_numeric($recharge_to_load)) {
        $this->id = $recharge_to_load;
        return $this;
      }

      $wpdb->insert(
        "{$wpdb->prefix}actionwear_recharges",
        [
          "recharge_type" => $this->recharge_type,
          "recharge_entity_id" => $this->recharge_entity_id,
          "name" => $this->name
        ]
      );
      $this->id = $wpdb->insert_id;
    }

    public static function getAll()
    {
      global $wpdb;
      $recharge_db_data = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}actionwear_recharges");
      $tables = [];
      foreach ($recharge_db_data as $id) {
        $tables[] = self::loadById($id)->getRaw();
      }
      return $tables;
    }

    public function getRaw()
    {
      return get_object_vars($this);
    }

    public static function loadById($id)
    {
      global $wpdb;
      $recharge = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}actionwear_recharges WHERE id = $id");
      if ($recharge === NULL) throw new \Exception("ID $id non trovato");
      $type = new RechargeType($recharge->recharge_type);
      $recharge = new self($type, $recharge->recharge_entity_id, $recharge->id);
      $recharge->loadDetails();
      return $recharge;
    }

    public static function entityIdExist($id)
    {
      global $wpdb;
      $recharge = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}actionwear_recharges WHERE recharge_entity_id = $id");
      return $recharge !== NULL;
    }

    public function loadDetails()
    {
      global $wpdb;
      if (!$this->id) throw new \Exception("Not valid recharge object");
      $details = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}actionwear_recharges_details WHERE id_recharge = $this->id");
      $this->details = $details;
    }

    public function updateDetails()
    {
      global $wpdb;
      if (!$this->id) throw new \Exception("Not valid recharge object");

      $current_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}actionwear_recharges_details WHERE id_recharge = $this->id");

      $passed_ids = [];
      foreach ($this->details as $detail) $passed_ids[] = $detail->id;

      $ids_to_delete = array_diff($current_ids, $passed_ids);

      foreach ($ids_to_delete as $id_to_delete) RechargeDetail::deleteById($id_to_delete);

      foreach ($this->details as $detail) {
        try {

          $detailObject = false;
          if (!empty($detail->id)) $detailObject = RechargeDetail::loadById($detail->id);

          if ($detailObject === false) {
            $detailObject = new RechargeDetail(null, null, null, true);
          }

          $detailObject->setPriceFrom($detail->price_from);
          $detailObject->setPriceTo($detail->price_to);
          $detailObject->setQuantityFrom($detail->quantity_from);
          $detailObject->setQuantityTo($detail->quantity_to);
          $detailObject->setPercent($detail->percent);

          if (!empty($detailObject->getId())) {
            $detailObject->update();
          } else {
            $recharge = self::loadById((int)$detail->id_recharge);
            $detailObject->addToRecharge($recharge);
          }
        } catch (\Exception $e) {
          throw $e;
        }
      }
      return true;
    }

    public function getId()
    {
      return $this->id;
    }

    public function getDetails()
    {
      return $this->details;
    }

    public function setDetails($details)
    {
      $this->details = $details;
      return $this;
    }

    public function getRechargeType()
    {
      return $this->recharge_type;
    }

    public function setRechargeType($recharge_type)
    {
      $this->recharge_type = $recharge_type;
      return $this;
    }

    public function getRechargeEntityId()
    {
      return $this->recharge_entity_id;
    }

    public function setRechargeEntityId($recharge_entity_id)
    {
      $this->recharge_entity_id = $recharge_entity_id;
      return $this;
    }

    public function getName()
    {
      return $this->name;
    }

    public function setName($name)
    {
      $this->name = $name;
      return $this;
    }

    /**
     * Check if brand recharge table exists and return it from Context
     *
     * @param int $id Brand id (recharge_entity_id)
     * @return false|Recharge False if brand doesn't exist. Recharge Object on success.
     **/
    public static function brand(int $id)
    {
      $context = Context::getContext();
      $context->loadRecharges();
      foreach ($context->recharges as $recharge) {
        if ($recharge["recharge_type"] === "brand" && (int)$recharge["recharge_entity_id"] === $id) return $recharge;
      }
      return false;
    }


    /**
     * Get general recharge Table from Context
     *
     * @return Recharge General recharge table
     **/
    public static function general()
    {
      $context = Context::getContext();
      $context->loadRecharges();
      foreach ($context->recharges as $recharge) {
        if ($recharge["recharge_type"] === "global" && (int)$recharge["recharge_entity_id"] === 0) return $recharge;
      }
    }

    /**
     * Check if category recharge table exists and return it from Context
     *
     * @param int $id category_ids (recharge_entity_id)
     * @return false|Recharge[] False if category doesn't exist. Array of Recharge Object that matches ids on success.
     **/
    public static function category(array $ids)
    {
      $context = Context::getContext();
      $context->loadRecharges();
      $recharges = [];
      foreach ($context->recharges as $recharge) {
        if ($recharge["recharge_type"] === "category" && in_array((int)$recharge["recharge_entity_id"], $ids)) $recharges[] = $recharge;
      }
      if (count($recharges) === 0) return false;
      return $recharges;
    }
  }
}
