<?php

namespace AC_SYNC\Includes\Classes\Category {
  if (!defined('ABSPATH')) exit; // Exit if accessed directly
  class Action_Wear_Category
  {

    const ORDER_DEFAULT = 0;
    const ORDER_NAMES_TREE = 1;

    /**
     * Crea una categoria su Woocommerce con un metodo diretto
     *
     * @param string $name Il nome della categoria
     * @param int $parent Se presente, inserisce la categoria come figlia del parent
     * @return array|false False in case category exist or cration failure, else returns term tree.
     * 
     * Useful keys are: term_id, slug, name
     **/
    public static function wcCreate(string $name, int $parent = 0)
    {
      $args = [];
      if ($parent > 0) {
        $args["parent"] = $parent;
      }
      $actionwear_category = wp_insert_term(
        $name,
        'product_cat',
        $args
      );
      return is_wp_error($actionwear_category) ? false : $actionwear_category;
    }

    public static function checkDefaultCategoryExist()
    {
      $actionwear_category = wp_insert_term(
        'Actionwear',
        'product_cat',
        [
          'description' => 'Actionwear Default category',
          'slug' => 'actionwear'
        ]
      );
      if (!is_wp_error($actionwear_category)) update_option("_ACTIONWEAR_DEFAULT_CATEGORY_ID", $actionwear_category["term_id"]);
    }

    public static function wcCreateByCamacTree($name)
    {
      $tree = explode(">", $name);
      $parent = 0;
      foreach ($tree as $category) {
        $category = trim($category);
        $exist = self::wcExistByName($category);
        if ($exist === false) {
          $exist = self::wcCreate($category, $parent);
        } else {
          $exist = self::wcGetByName($category);
        }
        $id = $exist->term_id ?? $exist["term_id"];
        $parent = $id;
      }
      return $exist;
    }

    public static function wcExistByName($name)
    {
      return get_term_by("name", $name, "product_cat") !== false;
    }

    public static function wcExistBySlug($slug)
    {
      return get_term_by("slug", $slug, "product_cat") !== false;
    }

    public static function wcGetByName($name)
    {
      return get_term_by("name", $name, "product_cat");
    }

    public static function wcGetBySlug($slug)
    {
      return get_term_by("slug", $slug, "product_cat");
    }

    public static function getAssociatedWcIds()
    {
      global $wpdb;
      return $wpdb->get_col("SELECT DISTINCT(wc_category_id) FROM {$wpdb->prefix}actionwear_categories_associations WHERE wc_category_id IS NOT NULL AND ignore_me = 0");
    }

    public static function getAllCategories()
    {
      global $wpdb;
      return $wpdb->get_results("SELECT *, name as camac_name FROM {$wpdb->prefix}actionwear_categories ORDER BY name");
    }

    public static function addChildrenNames(&$names, $categories, $id)
    {
      foreach ($categories as $category) {
        if ($category->id_actionwear == $id && $category->parent_id_actionwear != 1) {
          $names[] = $category->name;
          self::addChildrenNames($names, $categories, $category->parent_id_actionwear);
        }
      }
    }

    public static function getNamesTree($categories, $id)
    {
      $names_tree = [];
      foreach ($categories as $category) {
        if ($category->id_actionwear == $id && $category->parent_id_actionwear != 1) {
          self::addChildrenNames($names_tree, $categories, $id);
        }
      }
      $names_tree = array_reverse($names_tree);
      return implode(" > ", $names_tree);
    }

    public static function getAllCategoriesToAssociate()
    {
      global $wpdb;
      $all_categories = self::getAllCategories();
      $categories_to_associate_raw = $wpdb->get_results("SELECT *, name as camac_name FROM {$wpdb->prefix}actionwear_categories WHERE id NOT IN (SELECT ac.id FROM {$wpdb->prefix}actionwear_categories ac LEFT JOIN {$wpdb->prefix}actionwear_categories_associations AS aca ON aca.camac_category_id = ac.id_actionwear WHERE aca.wc_category_id IS NOT NULL AND aca.ignore_me = 0) ORDER BY name;");
      foreach ($categories_to_associate_raw as $category) {
        $category->name = self::getNamesTree($all_categories, $category->id_actionwear);
        $category->camac_name = self::getNamesTree($all_categories, $category->id_actionwear);
      }
      return $categories_to_associate_raw;
    }

    public static function getWcTreeByIdAndCategories($id, $categories)
    {
      $names = [];
      self::getWcCategoryTreeName($id, $categories, $names);
      return implode(" > ", array_reverse($names));
    }

    /**
     * Restituisce le categorie di WC in base all'ordine richiesto
     *
     * @param self::ORDER_DEFAULT|self::ORDER_NAMES_TREE $order Ordina in base al tipo richiesto.
     * 
     * self::ORDER_DEFAULT mantiene una struttura non gerarchica in ordine di Nome Ascendente.
     * 
     * self::ORDER_NAMES_TREE crea una struttura che inserisce nel nome l'intero albero di categoria, tenendo quindi conto anche dei parent.
     */
    public static function getAllWcCategories($order = self::ORDER_DEFAULT)
    {
      $data = [
        "taxonomy" => "product_cat",
        "hide_empty" => false,
        'orderby' => 'name',
        'order' => 'ASC',
        'exclude' => self::getAssociatedWcIds()
      ];
      $all = get_terms($data);
      if ($order === self::ORDER_NAMES_TREE) {
        $raw = get_terms($data);
        foreach ($all as $wc_category) {
          $wc_category->name = self::getWcTreeByIdAndCategories($wc_category->term_id, $raw);
        }
      }
      return $all;
    }

    /**
     * Lists all product categories and sub-categories in a tree structure.
     *
     * @return array
     */
    public static function wcListProductCategories()
    {
      $categories = get_terms(
        array(
          'taxonomy'   => 'product_cat',
          'orderby'    => 'name',
          'hide_empty' => false,
        )
      );

      $categories = self::treeifyTerms($categories);

      return $categories;
    }

    /**
     * Converts a flat array of terms into a hierarchical tree structure.
     *
     * @param WP_Term[] $terms Terms to sort.
     * @param integer   $root_id Id of the term which is considered the root of the tree.
     *
     * @return array Returns an array of term data. Note the term data is an array, rather than
     * term object.
     */
    public static function treeifyTerms($terms, $root_id = 0)
    {
      $tree = array();

      foreach ($terms as $term) {
        if ($term->parent === $root_id) {
          array_push(
            $tree,
            array(
              'name'     => $term->name,
              'slug'     => $term->slug,
              'id'       => $term->term_id,
              'count'    => $term->count,
              'children' => self::treeifyTerms($terms, $term->term_id),
            )
          );
        }
      }

      return $tree;
    }

    public static function getWcCategoryTreeName($id, $categories, &$names)
    {
      foreach ($categories as $category) {
        if ((int)$category->term_id === (int)$id) {
          $names[] = $category->name;
          if ((int)$category->parent !== 0) self::getWcCategoryTreeName($category->parent, $categories, $names);
        }
      }
    }

    public static function sortAlphabetically($data)
    {
      $letters = [];
      foreach ($data as $current_data) {
        $letter = strtoupper(substr($current_data->name, 0, 1));
        if (!array_key_exists($letter, $letters)) $letters[$letter] = [];
        $letters[$letter][] = $current_data;
      }
      ksort($letters);
      return $letters;
    }
  }
}
