<?php

namespace AC_SYNC\Includes\Classes\Recharge {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  class Action_Wear_Recharge_Detail
  {

    private $id;
    private $id_recharge;
    private $price_from;
    private $price_to;
    private $quantity_from;
    private $quantity_to;
    private $percent;

    public function getId()
    {
      return $this->id;
    }

    public function setId($id)
    {
      $this->id = $id;
      return $this;
    }

    public function getIdRecharge()
    {
      return $this->id_recharge;
    }

    public function setIdRecharge($id_recharge)
    {
      $this->id_recharge = $id_recharge;
      return $this;
    }

    public function getPriceFrom()
    {
      return $this->price_from;
    }

    public function setPriceFrom($price_from)
    {
      $this->price_from = $price_from;
      return $this;
    }

    public function getPriceTo()
    {
      return $this->price_to;
    }

    public function setPriceTo($price_to)
    {
      $this->price_to = $price_to;
      return $this;
    }

    public function getQuantityFrom()
    {
      return $this->quantity_from;
    }

    public function setQuantityFrom($quantity_from)
    {
      $this->quantity_from = $quantity_from;
      return $this;
    }

    public function getQuantityTo()
    {
      return $this->quantity_to;
    }

    public function setQuantityTo($quantity_to)
    {
      $this->quantity_to = $quantity_to;
      return $this;
    }

    public function getPercent()
    {
      return $this->percent;
    }

    public function setPercent($percent)
    {
      $this->percent = $percent;
      return $this;
    }


    public function __construct(
      $price = [
        "price_from" => 0.01,
        "price_to" => 5
      ],
      $quantity = [
        "quantity_from" => 1,
        "quantity_to" => 99999
      ],
      $percent = 30,
      $empty = false
    ) {

      if ($empty === true) return;

      // validity checks
      [
        "price_from" => $pf,
        "price_to" => $pt
      ] = $price;

      [
        "quantity_from" => $qf,
        "quantity_to" => $qt
      ] = $quantity;

      $pf = (float)$pf;
      $pt = (float)$pt;
      $qf = (int)$qf;
      $qt = (int)$qt;
      $percent = (int)$percent;

      if ($pf > $pt) throw new \Exception("'Prezzo da' non può essere maggiore di 'Fino a'");
      if ($pf <= 0) throw new \Exception("'Prezzo da' non può essere minore o uguale a zero");
      if ($qf > $qt) throw new \Exception("'Quantità da' non può essere maggiore di 'Fino a'");
      if ($qf <= 0) throw new \Exception("La quantità non può essere minore o uguale a zero");
      if ($percent <= 0) throw new \Exception("La percentuale non può essere minore o uguale a zero");

      $this->setPriceFrom($pf);
      $this->setPriceTo($pt);
      $this->setQuantityFrom($qf);
      $this->setQuantityTo($qt);
      $this->setPercent($percent);

      return $this;
    }

    public static function loadById($id)
    {
      global $wpdb;
      $detail = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}actionwear_recharges_details WHERE id = $id");
      if ($detail === NULL) return false;
      $detailObject = new self(
        [
          "price_from" => $detail->price_from,
          "price_to" => $detail->price_to
        ],
        [
          "quantity_from" => $detail->quantity_from,
          "quantity_to" => $detail->quantity_to
        ],
        $detail->percent
      );
      $detailObject->setId($detail->id);
      $detailObject->setIdRecharge($detail->id_recharge);
      return $detailObject;
    }

    public function update()
    {
      global $wpdb;
      if (!$this->id) throw new \Exception("Cannot update without ID");

      $pf = (float)$this->getPriceFrom();
      $pt = (float)$this->getPriceTo();
      $qf = (int)$this->getQuantityFrom();
      $qt = (int)$this->getQuantityTo();
      $percent = (int)$this->getPercent();

      if ($pf > $pt) throw new \Exception("'Prezzo da' non può essere maggiore di 'Fino a'");
      if ($pf <= 0) throw new \Exception("'Prezzo da' non può essere minore o uguale a zero");
      if ($qf > $qt) throw new \Exception("'Quantità da' non può essere maggiore di 'Fino a'");
      if ($qf <= 0) throw new \Exception("La quantità non può essere minore o uguale a zero");
      if ($percent <= 0) throw new \Exception("La percentuale non può essere minore o uguale a zero");

      return $wpdb->update(
        "{$wpdb->prefix}actionwear_recharges_details",
        [
          "price_from" => $pf,
          "price_to" => $pt,
          "quantity_from" => $qf,
          "quantity_to" => $qt,
          "percent" => $percent
        ],
        [
          "id" => $this->id
        ]
      );
    }

    public function addToRecharge(Action_Wear_Recharge $recharge)
    {

      global $wpdb;
      $id = $recharge->getId();
      $wpdb->insert(
        "{$wpdb->prefix}actionwear_recharges_details",
        [
          "id_recharge" => $id,
          "price_from" => $this->getPriceFrom(),
          "price_to" => $this->getPriceTo(),
          "quantity_from" => $this->getQuantityFrom(),
          "quantity_to" => $this->getQuantityTo(),
          "percent" => $this->getPercent(),
        ]
      );
      return $wpdb->insert_id;
    }

    public static function deleteById($id)
    {
      global $wpdb;
      return $wpdb->delete(
        "{$wpdb->prefix}actionwear_recharges_details",
        [
          "id" => $id
        ]
      );
    }

    public function delete()
    {
      global $wpdb;
      if (!$this->id) throw new \Exception("Cannot update without ID");
      return $wpdb->delete(
        "{$wpdb->prefix}actionwear_recharges_details",
        [
          "id" => $this->id
        ]
      );
    }
  }
}
