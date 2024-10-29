<?php

namespace AC_SYNC\Includes\Classes\Product {


  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
  use AC_SYNC\Action_Wear_Core;
  use AC_SYNC\Includes\Classes\Api\Action_Wear_Api as Api;
  use AC_SYNC\Includes\Classes\Utils\Action_Wear_Utils;
  use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
  use AC_SYNC\Includes\Classes\Product\Action_Wear_Product_Image as ProductImage;
  use AC_SYNC\Includes\Classes\Price\Action_Wear_Price_Recharger as PriceRecharger;
  use AC_SYNC\Includes\Classes\Category\Action_Wear_Category_Association as CategoryAssociation;
  use AC_SYNC\Includes\Classes\Attribute\Action_Wear_Attribute as Attribute;
  use AC_SYNC\Includes\Classes\Setting\Action_Wear_Setting_Debug_Mode as DebugMode;
  use ArrayIterator;

  // HOTFIX for woocommerce functions
  //TODO: [AD-25] Remove this when woocommerce fix it
  require_once dirname(__FILE__) . '/../../../../woocommerce/includes/admin/wc-admin-functions.php';

  class Action_Wear_Product
  {
    const SKIP_IMAGES = false;
    const SKIP_PRICE_RECHARGE = false;

    public static function getPrice($simple, $conf)
    {
      $base_price = $simple->prices->{1}->price;
      return self::getRechargedPrice($conf, $base_price);
    }

    public static function getRechargedPrice($conf, $price)
    {
      $base_price = $price;
      if (self::SKIP_PRICE_RECHARGE)
        return $base_price;
      $price_recharger = new PriceRecharger($base_price);
      $categories = (array) $conf->categories;
      $cat_ids = array_keys($categories);
      return $price_recharger->getPrice((int) $conf->brand_id, $cat_ids);
    }

    public static function createVariantBySimple($product, $simple, $conf, $dimensions, $cover_id)
    {
      $conf_id = $conf->get_id();
      $variation = new \WC_Product_Variation(wc_get_product_id_by_sku($simple->sku));
      $variation->set_parent_id($conf_id);

      $color = $simple->nome_colore;
      $term = get_term_by("name", $color, "pa_colore");
      $color = $term->slug;

      $size = $simple->camac_size;
      $term = get_term_by("name", $size, "pa_taglia");
      $size = $term->slug;

      $variation->set_attributes(
        [
          "pa_colore" => $color,
          "pa_taglia" => $size
        ]
      );

      $variation->set_sku($simple->sku);
      $price = self::getPrice($simple, $conf);
      if ((float) $price === 0) {
        $variation->delete(true);
        return false;
      }
      $variation->set_regular_price($price);
      $variation->set_manage_stock(true);
      $variation->set_stock_quantity($simple->stocks->available);
      $variation->save();
      $categories = (array) $product->categories;
      $cat_ids = array_keys($categories);
      $brand_id = $product->brand_id;
      update_post_meta($variation->get_id(), "cat_ids", json_encode($cat_ids));
      update_post_meta($variation->get_id(), "brand_id", $brand_id);
      update_post_meta($variation->get_id(), "original_base_price", $simple->prices->{1}->price);
      update_post_meta($variation->get_id(), "quantity_supplier", (float) $simple->stocks->supplier);
      update_post_meta($variation->get_id(), "total_arrivals", (float) $simple->stocks->total_arrivals);
      update_post_meta($variation->get_id(), "supplier_days", (float) $simple->stocks->giorniConsegnaFornitore);
      if ($simple->stocks->arrivals_detail !== null) {
        $arrivals_detail = [];
        foreach ($simple->stocks->arrivals_detail as $arrival_detail) {
          $arrivals_detail[] = [
            "data_arrivi" => $arrival_detail->dataArriviOrig ?? "",
            "numero_giorni" => (int) $arrival_detail->numeroGiorni ?? 0,
            "settimana_arrivi" => $arrival_detail->settimanaArrivi ?? "",
            "qta" => (int) $arrival_detail->qta ?? 0,
          ];
        }
        update_post_meta($variation->get_id(), "arrivals_detail", json_encode($arrivals_detail));
      }
      if ($simple->stocks->supplier_detail !== null) {
        $supplier_detail = [];
        foreach ($simple->stocks->supplier_detail as $supply_detail) {
          $supplier_detail[] = [
            "data_arrivi" => $supply_detail->dataArriviOrig ?? "",
            "numero_giorni" => (int) $supply_detail->numeroGiorni ?? 0,
            "settimana_arrivi" => $supply_detail->settimanaArrivi ?? "",
            "qta" => (int) $supply_detail->qta ?? 0,
          ];
        }
        update_post_meta($variation->get_id(), "supplier_detail", json_encode($supplier_detail));
      }
      $variation->set_weight($simple->product_weight);
      if (isset($dimensions)) {
        if (isset($dimensions->l))
          $variation->set_length($dimensions->l);
        if (isset($dimensions->h))
          $variation->set_length($dimensions->h);
      }
      if ($cover_id !== false)
        $variation->set_image_id($cover_id);
      return $variation->save();
    }

    public static function getRemoteProductsListino(array $skus)
    {
      $skus = implode(",", $skus);
      $products = Api::external("GET", "/listino?sku=$skus&output=json&compress=0", ["timeout" => 60]);
      $code = $products["code"];
      if ($code !== 200) {
        Log::write("Errore durante la lettura, con codice di stato: $code - degli sku remoti (listino) : " . $skus, Log::ERROR, Log::CONTEXT_CAMAC_API);
        throw new \Exception("Si è verificato un errore nell'acquisizione dei prodotti, codice di errore: $code - /listino: " . $skus);
      }
      $products = json_decode($products["raw"]["body"]);
      return $products[0];
    }

    public static function createOrUpdateWcProduct($product)
    {

      $simple_products = $product->simple_products;

      $_product = new \WC_Product_Variable(wc_get_product_id_by_sku($product->sku));
      $_product->set_name($product->name);
      $_product->set_sku($product->sku);
      $_product->set_short_description($product->short_description);
      $_product->set_description($product->description ?? "");
      $cat_ids = CategoryAssociation::getAssociationByProduct($product->sku);
      $_product->set_category_ids($cat_ids);

      if (DebugMode::getDebugMode())
        Log::write("Oggetto product variant istanziato per sku $product->sku", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
      if (DebugMode::getDebugMode())
        Log::write("Istanziati gli attributi colore e taglia per sku $product->sku", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
      if (DebugMode::getDebugMode())
        Log::write("Istanziati gli extra attributi per sku $product->sku", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);

      $_product->set_attributes(Attribute::setAttributes($product));
      $_product->save();


      if (DebugMode::getDebugMode())
        Log::write("Primo salvataggio oggetto product per sku $product->sku", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);

      if (!self::SKIP_IMAGES && !empty($product->sku)) {
        $productImage = new ProductImage($product->sku, $_product->get_id());
        if (get_option("_ACTIONWEAR_USE_CONFIGURABLE", "0") == "0") {
          $simple_products = $product->simple_products;
          $simple_products = new ArrayIterator($simple_products);
          $configurable_images = $simple_products->current()->immagini_colore;
        } else {
          $configurable_images = ProductImage::getImagesBySku($product->sku);
        }
        $image_ids = $productImage->setConfigurableImages($configurable_images);
        $cover_id = false;
        if (count($image_ids) > 0)
          $cover_id = $image_ids[0];
        if ($cover_id !== false)
          $_product->set_image_id($cover_id);
        $_product->set_gallery_image_ids($image_ids);
        if (DebugMode::getDebugMode())
          Log::write("Acquisite e salvate immagini configurabile per sku $product->sku", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
      }

      if (DebugMode::getDebugMode())
        Log::write("Inizio a creare le varianti dello sku $product->sku", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);

      foreach ($simple_products as $simple) {

        $cover = false;

        if (!self::SKIP_IMAGES) {
          $simple_images = ProductImage::getImagesBySku($simple->sku);
          if (count($simple_images) > 0) {
            $image_ids = $productImage->setSimpleImagesBySku($simple->sku, $simple_images);
            if (DebugMode::getDebugMode())
              Log::write("Impostate le immagini del semplice con sku $simple->sku", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
            if (count($image_ids) > 0) {
              $cover = $image_ids[0];
            }
          }
        }

        $dimensions = (isset($product->camac_misure) && !empty($product->camac_misure) && isset($product->camac_misure->{$simple->camac_size})) ? $product->camac_misure->{$simple->camac_size} : NULL;
        self::createVariantBySimple($product, $simple, $_product, $dimensions, $cover);
        $_product->save();
        if (DebugMode::getDebugMode())
          Log::write("Creata la variante con sku $simple->sku", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
      }

      // check for variations that doesn't exist anymore

      // get all skus from actionwear response
      $actionwear_skus = array_map(function ($item) {
        return $item->sku;
      }, (array) $simple_products);
      $actionwear_skus = array_values($actionwear_skus);

      // get all product children skus
      $children = $_product->get_children();
      $children_skus = array_map(function ($item) {
        return get_post_meta($item, '_sku', true);
      }, $children);

      // if there are skus on product children that are not in actionwear response, delete them
      // get sku to delete
      $to_delete = array_diff($children_skus, $actionwear_skus);

      foreach ($to_delete as $sku) {
        $id_simple = wc_get_product_id_by_sku($sku);
        $variation = new \WC_Product_Variation($id_simple);
        if ($variation !== false) {
          $variation->delete(true);
        }
      }

      if (!self::SKIP_IMAGES)
        $productImage->saveToMeta();

      $_product->save();
      self::setProductAsCreated($product->sku);
      if (DebugMode::getDebugMode())
        Log::write("Ultimo salvataggio del prodotto $product->sku e set wc_created = 1", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
    }

    public static function setProductAsCreated($sku)
    {
      global $wpdb;
      return $wpdb->update(
        "{$wpdb->prefix}actionwear_products",
        [
          "wc_created" => 1
        ],
        [
          "sku" => $sku
        ]
      );
    }

    public static function deleteAll($only_created = true)
    {
      global $wpdb;
      $wc_created = $only_created === true ? 1 : 0;
      $products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}actionwear_products WHERE wc_to_create = 1 AND wc_created = $wc_created");
      foreach ($products as $product) {
        $sku = $product->sku;
        $id = $wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '$sku'");
        if ($id !== false) {
          $wpdb->query("DELETE FROM {$wpdb->prefix}posts WHERE ID = '$id'");
          $wpdb->query("DELETE FROM {$wpdb->prefix}posts WHERE post_parent = '$id'");
        }
      }
    }

    // $selection can accept one of ALL | PRODUCTS | CATEGORIES
    public static function setProductsToImportBySelection($selection, $productsData)
    {
      global $wpdb;
      $update = false;

      if ($selection === "ALL") {
        $update = $wpdb->query("UPDATE " . $wpdb->prefix . "actionwear_products SET wc_to_create = 1;") !== FALSE;
        $skus = $wpdb->get_col("SELECT sku FROM " . $wpdb->prefix . "actionwear_products;");
        foreach ($skus as $sku) {
          Action_Wear_Core::$importProcess->push_to_queue($sku);
        }
        Action_Wear_Core::$importProcess->save();
      }

      if ($selection === "PRODUCTS") {
        $ids = implode(",", $productsData->ids);
        $wpdb->query("UPDATE " . $wpdb->prefix . "actionwear_products SET wc_to_create = 0;");
        $update = $wpdb->query("UPDATE " . $wpdb->prefix . "actionwear_products SET wc_to_create = 1 WHERE id IN ($ids);") !== FALSE;
        // add products to async queue
        $skus = $wpdb->get_col("SELECT sku FROM " . $wpdb->prefix . "actionwear_products WHERE id IN ($ids);");
        foreach ($skus as $sku) {
          Action_Wear_Core::$importProcess->push_to_queue($sku);
        }
        Action_Wear_Core::$importProcess->save();
      }

      if ($selection === "CATEGORIES") {
        $category_ids = $productsData->category_ids;
        $category_ids = implode(",", $category_ids);
        $update = $wpdb->query("UPDATE " . $wpdb->prefix . "actionwear_products SET wc_to_create = 1 WHERE id IN (SELECT DISTINCT(id_product) FROM " . $wpdb->prefix . "actionwear_categories_product WHERE id_category IN (" . $category_ids . "));") !== FALSE;
        // add products to async queue
        $skus = $wpdb->get_col("SELECT sku FROM " . $wpdb->prefix . "actionwear_products WHERE id IN (SELECT DISTINCT(id_product) FROM " . $wpdb->prefix . "actionwear_categories_product WHERE id_category IN (" . $category_ids . "));");
        foreach ($skus as $sku) {
          Action_Wear_Core::$importProcess->push_to_queue($sku);
        }
        Action_Wear_Core::$importProcess->save();
      }

      return $update;
    }

    public static function tableIsEmpty()
    {
      global $wpdb;
      return (int) $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "actionwear_products") === 0;
    }

    public static function addCategoriesQueries($cats_tree, &$categories_queries)
    {
      global $wpdb;
      foreach ($cats_tree as $category) {
        $categories_queries[] = $wpdb->prepare(
          "(%d, %d, %s, %d, %d, %d, %d)",
          $category->id,
          $category->parent_id,
          $category->name,
          $category->is_active,
          $category->product_count,
          $category->position,
          $category->level
        );
        if (count($category->children_data) > 0)
          self::addCategoriesQueries($category->children_data, $categories_queries);
      }
    }

    public static function generateCategories()
    {

      global $wpdb;
      $categories = Api::externalMagento("/categories", [], "?a=1");
      $code = $categories["code"];
      if ($code !== 200) {
        $error = "Si è verificato un errore nell'acquisizione delle categories da /categories, codice di stato: $code";
        Log::write($error, Log::ERROR, Log::CONTEXT_CAMAC_API, __LINE__, __FILE__);
        throw new \Exception($error);
      }
      $categories = json_decode($categories["raw"]["body"]);

      $categories_queries = [];

      $categories_queries[] = $wpdb->prepare(
        "(%d, %d, %s, %d, %d, %d, %d)",
        $categories->id,
        $categories->parent_id,
        $categories->name,
        $categories->is_active,
        $categories->product_count,
        $categories->position,
        $categories->level
      );

      self::addCategoriesQueries($categories->children_data, $categories_queries);

      $query = "INSERT IGNORE INTO " . $wpdb->prefix . "actionwear_categories (id_actionwear, parent_id_actionwear, name, is_active, product_count, position, level) VALUES " . implode(",", $categories_queries);
      $wpdb->query($query);

      $generated_ids = $wpdb->get_col("SELECT id_actionwear FROM {$wpdb->prefix}actionwear_categories");
      $query = "INSERT IGNORE INTO " . $wpdb->prefix . "actionwear_categories_associations (camac_category_id) VALUES (" . implode("),(", $generated_ids) . ")";
      $wpdb->query($query);
    }

    public static function generateBrands()
    {
      global $wpdb;
      $brands = Api::externalMagento("/products/attributes/mgs_brand", [], "?a=1");
      $code = $brands["code"];
      if ($code !== 200) {
        $error = "Si è verificato un errore nell'acquisizione dei brands da /products/attributes/mgs_brand";
        Log::write($error, Log::ERROR, Log::CONTEXT_CAMAC_API, __LINE__, __FILE__);
        throw new \Exception($error);
      }
      $body = json_decode($brands["raw"]["body"]);
      $options = $body->options;
      $values = [];
      foreach ($options as $option) {

        [
          "label" => $name,
          "value" => $id
        ] = get_object_vars($option);
        if (empty(trim($name)))
          continue;
        $values[] = $wpdb->prepare("(%d, %s)", $id, $name);
      }

      $query = "INSERT IGNORE INTO " . $wpdb->prefix . "actionwear_brands (id_actionwear, name) VALUES " . implode(",", $values);
      $wpdb->query($query);

      return true;
    }

    public static function isCamacThumbnail($thumbnail_id)
    {
      return (int) get_post_meta($thumbnail_id, "camac_product_id", true) > 0;
    }

    public static function isCamacProduct($product_id)
    {
      return get_post_meta($product_id, "camac_cdn_urls", true) !== "";
    }

    public static function getQuantitiesDetail($id)
    {
      $keys = ["quantity_supplier", "total_arrivals", "arrivals_detail", "supplier_detail", "supplier_days"];
      $to_decode = [$keys[2], $keys[3]];
      $result = [];
      foreach ($keys as $key) {
        $result[$key] = in_array($key, $to_decode) ? json_decode(get_post_meta($id, $key, true)) : get_post_meta($id, $key, true);
      }
      return $result;
    }

    public static function getQuantitiesDetails(array $ids)
    {
      $result = [];
      foreach ($ids as $id) {
        $result[$id] = self::getQuantitiesDetail($id);
      }
      return $result;
    }

    public static function checkDefaultAttributesCreation()
    {
      if (!class_exists("woocommerce")) {
        $error = new \WP_Error('500', "Per poter attivare questo plugin devi avere installato ed attivato il plugin di WooCommerce");
        if (is_wp_error($error)) {
          wp_die($error);
        }
      }
      $attributes = wc_get_attribute_taxonomies();
      $names = [];
      foreach ($attributes as $attribute) {
        $names[] = $attribute->attribute_label;
      }
      if (!in_array("Colore", $names)) {
        wc_create_attribute([
          "name" => "Colore",
          "has_archives" => true
        ]);
      }
      if (!in_array("Taglia", $names)) {
        wc_create_attribute([
          "name" => "Taglia",
          "has_archives" => true
        ]);
      }
      if (!in_array("Color Group", $names)) {
        wc_create_attribute([
          "name" => "Color Group",
          "slug" => "camac_color_group",
          "has_archives" => true
        ]);
      }
    }

    public static function generateImages()
    {
      global $wpdb;
      $images = Api::external("GET", "/productsimage", ["timeout" => 30000]);
      $code = $images["code"];
      if ($code !== 200) {
        $error = "Si è verificato un errore nell'acquisizione delle immagini da /productsimages con codice di stato: $code";
        Log::write($error, Log::ERROR, Log::CONTEXT_CAMAC_API, __LINE__, __FILE__);
        throw new \Exception($error);
      }
      $images = json_decode($images["raw"]["body"]);
      $values = [];
      $i = 0;
      $portion = 0;
      $values[] = [];
      foreach ($images as $image) {
        if ($i % 3000 === 0) {
          $values[] = [];
          $portion++;
        }
        $sku = trim($image->sku);
        $values[$portion][] = "('$sku','" . json_encode($image->media_gallery) . "')";
        $i++;
      }
      foreach ($values as $values_portion) {
        $raw = trim(implode(",", $values_portion));
        if (empty($raw))
          continue;
        $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}actionwear_images (sku, images) VALUES $raw");
      }
      return true;
    }

    public static function filterDeletedProducts()
    {
      global $wpdb;

      try {
        $pricesAll = Api::external(
          "GET",
          "/pricesall?compress=0&output=json"
        );
      } catch (\Exception $e) {
        Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_CAMAC_API);
        throw new \Exception($e->getMessage());
      }

      $pricesAll = json_decode($pricesAll['raw']['body'], true);
      $currentProducts = $wpdb->get_col("SELECT sku FROM `{$wpdb->prefix}actionwear_products`");

      $output = [];

      foreach ($pricesAll[0] as $price) {
        if (in_array($price['sku_camac'], $output))
          continue;
        $output[] = $price['sku_camac'];
      }

      $deletedProducts = array_diff($currentProducts, $output);

      foreach ($deletedProducts as $deletedSku) {
        $wpdb->query("DELETE FROM `{$wpdb->prefix}actionwear_products` WHERE sku = '{$deletedSku}'");
      }
    }

    public static function generateProducts()
    {

      global $actionwear_plugin_path, $wpdb;

      try {
        $url = self::getUrlPricesAll();
      } catch (\Exception $e) {
        Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_CAMAC_API);
        throw new \Exception($e->getMessage());
      }

      $plugin_base = $actionwear_plugin_path;
      $tmp = download_url($url);
      if (is_wp_error($tmp))
        Log::write("Errore durante il download di $url, " . json_encode($tmp->get_error_messages()), Log::CRITICAL);
      $local_tmp = $plugin_base . "tmp" . DIRECTORY_SEPARATOR . "tmp.zip";
      $tmp_dir = $plugin_base . "tmp" . DIRECTORY_SEPARATOR;
      copy($tmp, $local_tmp);
      $zip = new \ZipArchive;
      $res = $zip->open($local_tmp);
      if ($res === TRUE) {
        $zip->extractTo($tmp_dir);
        $zip->close();
        $extracted = glob($tmp_dir . "*.csv")[0];
        $csv_content = array_map('str_getcsv', explode("\n", file_get_contents($extracted)));
        array_walk($csv_content, function (&$a) use ($csv_content) {
          if (count($csv_content[0]) !== count($a)) {
            return;
          }
          $a = @array_combine($csv_content[0], $a);
        });
        array_shift($csv_content);
        unlink($local_tmp);
        unlink($extracted);
        $query_values = [];
        $processed = [];
        if (empty($csv_content)) {
          Log::write("pricesall è vuoto", Log::ERROR, Log::CONTEXT_CAMAC_API);
          update_option("_ACTIONWEAR_API_ERROR", 1);
          throw new \Exception("Il listino è vuoto");
        }
        foreach ($csv_content as $row) {
          if (!isset($row["sku_camac"]))
            continue;
          [
            "sku_camac" => $sku_camac
          ] = $row;
          if (!in_array($sku_camac, $processed) && !empty($sku_camac)) {
            $processed[] = $sku_camac;
            $query_values[] = $wpdb->prepare("(%s, %d)", $sku_camac, 1);
          }
        }
        $query = "INSERT IGNORE INTO " . $wpdb->prefix . "actionwear_products (sku, is_configurable) VALUES " . implode(',', $query_values) . ";";
        $wpdb->query($query);

        try {
          $url = self::getUrlListino();
        } catch (\Exception $e) {
          Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_CAMAC_API);
          update_option("_ACTIONWEAR_API_ERROR", 1);
          throw new \Exception($e->getMessage());
        }

        $tmp = download_url($url);
        if (is_wp_error($tmp))
          Log::write("Errore durante il download di $url, " . json_encode($tmp->get_error_messages()), Log::CRITICAL, Log::CONTEXT_CAMAC_API);
        copy($tmp, $local_tmp);
        $zip = new \ZipArchive;
        $res = $zip->open($local_tmp);

        if ($res === TRUE) {
          $zip->extractTo($tmp_dir);
          $zip->close();
          $extracted = glob($tmp_dir . "*.json")[0];
          $listino = json_decode(file_get_contents($extracted));
          unlink($local_tmp);
          unlink($extracted);
          $categories_query = [];
          foreach ($listino->hierarchical as $sku => $product) {

            $brand = $product->brand ?? "";
            $brand_id = $product->brand_id ?? 0;
            if (!empty($brand) && !empty($brand_id)) {
              $query = "INSERT IGNORE INTO " . $wpdb->prefix . "actionwear_brands (id_actionwear, name) VALUES ('$brand_id', '$brand')";
              $wpdb->query($query);
            }
            $name = $product->name ?? "";
            $cover = $product->immagini_base[0] ?? "";
            if (!empty($cover)) {
              $cover = str_replace("://action-wear.com", "://media.action-wear.com", $cover);
            }

            if (count((array) $product->categories) > 0) {
              $categories = array_keys((array) $product->categories);
              $pid = $wpdb->get_var("SELECT id from " . $wpdb->prefix . "actionwear_products WHERE sku = '$sku'");
              if ($pid) {
                foreach ($categories as $cat) {
                  $categories_query[] = "($pid, $cat)";
                }
              }
            }

            $update_query = "UPDATE {$wpdb->prefix}actionwear_products SET cover = %s, name = %s, brand_name = '{$brand}' WHERE sku = %s;";
            $wpdb->query($wpdb->prepare($update_query, $cover, $name, $sku));
          }
          if (count($categories_query) > 0) {
            $i = 0;
            $portion = 0;
            $partial = [];
            $partial[] = [];
            foreach ($categories_query as $category_query) {
              if ($i % 3000 === 0) {
                $partial[] = [];
                $portion++;
              }
              $partial[$portion][] = $category_query;
              $i++;
            }
            foreach ($partial as $part) {
              $raw = implode(",", $part);
              $query = "INSERT IGNORE INTO {$wpdb->prefix}actionwear_categories_product (id_product, id_category) VALUES {$raw};";
              $wpdb->query($query);
            }
          }
        }
      }
    }

    public static function getUrlPricesAll()
    {
      $all_products = Api::external("GET", "/pricesall");
      $code = $all_products["code"];
      if ($code !== 200) {
        $error = "Si è verificato un errore nell'acquisizione del catalogo pricesall con codice di stato: $code";
        Log::write($error, Log::ERROR, Log::CONTEXT_CAMAC_API, __LINE__, __FILE__);
        throw new \Exception($error);
      }
      $body = json_decode($all_products["raw"]["body"]);
      if (Action_Wear_Utils::isJson($body))
        $body = json_decode($body);
      if (isset($body->error)) {
        $error = "Il listino è in elaborazione...";
        Log::write($error, Log::ERROR, Log::CONTEXT_CAMAC_API, __LINE__, __FILE__);
        throw new \Exception($error);
      }
      return $body;
    }

    public static function getUrlListino()
    {
      $all_products = Api::external("GET", "/listino?output=json&noprice=1", ["timeout" => 90]);
      $code = $all_products["code"];
      if ($code !== 200) {
        $error = "Si è verificato un errore nell'acquisizione del catalogo listino con codice di stato: $code";
        Log::write($error, Log::ERROR, Log::CONTEXT_CAMAC_API, __LINE__, __FILE__);
        throw new \Exception($error);
      }
      $body = json_decode($all_products["raw"]["body"]);
      if (Action_Wear_Utils::isJson($body))
        $body = json_decode($body);
      if (isset($body->error)) {
        $error = "Il listino è in elaborazione...";
        Log::write($error, Log::ERROR, Log::CONTEXT_CAMAC_API, __LINE__, __FILE__);
        throw new \Exception($error);
      }
      return $body;
    }

    public function testBoolGetRemoteListino($sku)
    {
      $result = $this->getRemoteProductsListino([$sku]);
      return (bool) $result;
    }
  }
}



