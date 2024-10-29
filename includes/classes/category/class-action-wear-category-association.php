<?php

namespace AC_SYNC\Includes\Classes\Category {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Category\Action_Wear_Category as Category;

  /**
   * Gestisce la creazione e modifica della mappatura delle categorie tra quelle di Actionwear e quelle dell'istanza di Woocommerce.
   * 
   * Di default la tabella actionwear_categories_associations si popola con valori nulli durante il primo import.
   * Senza alcuna modifica alle categorie, tutti i prodotti vengono assegnati ad una categoria chiamata "Actionwear".
   */
  class Action_Wear_Category_Association
  {

    public $id;

    /**
     * Setta per quella determinata categoria di Actionwear (camac) la corrispettiva categoria Woocommerce mappata.
     * 
     * @param int|NULL $wc_category_id Quando a NULL ed il flag ignore_me = false, la categoria non viene mappata con una corrispendente
     * ed il sistema provvede a crearne una NUOVA ed aggiornare subito dopo il wc_category_id.
     * @throws Exception Se l'id non viene trovato
     **/
    public function __construct(int $camac_category_id, int $wc_category_id = NULL)
    {
      global $wpdb;
      $id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}actionwear_categories_associations WHERE camac_category_id = '$camac_category_id'");
      if ($id === false) throw new \Exception("Camac category id: $camac_category_id non trovato.");
      $wpdb->update(
        $wpdb->prefix . "actionwear_categories_associations",
        [
          "wc_category_id" => $wc_category_id
        ],
        [
          "camac_category_id" => $camac_category_id
        ]
      );
      $this->id = $id;
    }

    public static function deleteByWcId(int $wc_id)
    {
      global $wpdb;
      return $wpdb->update(
        $wpdb->prefix . "actionwear_categories_associations",
        [
          "wc_category_id" => NULL,
          "ignore_me" => true
        ],
        [
          "wc_category_id" => $wc_id
        ]
      );
    }

    /**
     * Imposta l'ignore_me per l'oggetto corrente
     *
     * @param bool $flag Quando questo flag è true, durante l'import dei prodotti la gestione della categoria viene ignorata per essa mentre invece viene associata
     * la categoria default "Actionwear".
     * 
     * Per fare in modo che il sistema crei dinamicamente le categorie provenienti da Actionwear questo flag deve essere impostato a false e wc_category_id a NULL.
     * 
     * @return int Il numero delle righe aggiornate. Dovrebbe essere sempre 1.
     * @throws Exception Se non è stata caricata correttamente un'istanza di questo oggetto
     **/
    public function ignoreMe(bool $flag = true)
    {
      global $wpdb;
      if (!$this->id) throw new \Exception("Istanza non valida o non caricata");
      return $wpdb->update(
        $wpdb->prefix . "actionwear_categories_associations",
        [
          "ignore_me" => $flag === true ? 1 : 0
        ],
        [
          "id" => $this->id
        ]
      );
    }

    public static function getAllAssociations()
    {
      global $wpdb;
      $associations = [];
      $query = <<<SQL
        SELECT aca.*, ac.name camac_name, t.name wc_name
        FROM {$wpdb->prefix}actionwear_categories_associations aca
        JOIN {$wpdb->prefix}actionwear_categories ac ON ac.id_actionwear = aca.camac_category_id
        JOIN {$wpdb->prefix}terms t ON t.term_id = aca.wc_category_id
        WHERE aca.wc_category_id IS NOT NULL AND aca.ignore_me = 0
        ORDER by aca.id DESC;
SQL;
      $_associations = $wpdb->get_results($query);
      $data = [
        "taxonomy" => "product_cat",
        "hide_empty" => false,
        'orderby' => 'name',
        'order' => 'ASC'
      ];
      $all = get_terms($data);
      $all_camac = Category::getAllCategories();
      foreach ($_associations as $_association) {
        if (!array_key_exists($_association->wc_category_id, $associations)) $associations[$_association->wc_category_id] = [];
        $_association->wc_name = Category::getWcTreeByIdAndCategories((int)$_association->wc_category_id, $all);
        $_association->camac_name = Category::getNamesTree($all_camac, (int)$_association->camac_category_id);
        $associations[$_association->wc_category_id][] = $_association;
      }
      return $associations;
    }

    public static function getAssociationByProduct($sku)
    {
      global $wpdb;
      $pf = $wpdb->prefix;
      $query = "SELECT aca.wc_category_id FROM " . $pf . "actionwear_categories_associations aca JOIN " . $pf . "actionwear_categories ac ON ac.id_actionwear = aca.camac_category_id JOIN " . $pf . "actionwear_categories_product acp ON ac.id_actionwear = acp.id_category AND acp.id_product = (SELECT id FROM " . $pf . "actionwear_products WHERE sku = '$sku') WHERE aca.wc_category_id IS NOT NULL AND aca.ignore_me = 0";
      $association = $wpdb->get_col($query);
      if (count($association) === 0) return [get_option("_ACTIONWEAR_DEFAULT_CATEGORY_ID")];
      return $association;
    }
  }
}
