<?php

namespace AC_SYNC {
  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Api\Action_Wear_Api;
  use AC_SYNC\Includes\Classes\Sql\Action_Wear_Sql_Manager;
  use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
  use AC_SYNC\Includes\Classes\Product\Action_Wear_Product_Image as ProductImage;
  use AC_SYNC\Includes\Classes\Utils\Action_Wear_Utils as Utils;
  use AC_SYNC\Includes\Classes\Cron\Action_Wear_Cron as Cron;
  use AC_SYNC\Includes\Classes\Sql\Action_Wear_Sql_Versioning as Sql;
  use AC_SYNC\Includes\Classes\Category\Action_Wear_Category as Category;
  use AC_SYNC\Includes\Classes\Async\Action_Wear_Product_Import as ProductImport;
  use AC_SYNC\Includes\Classes\Async\Action_Wear_Differential_Product_Task as DifferentialProductTask;
  use AC_SYNC\Includes\Classes\Async\Action_Wear_Prices_Update as PricesUpdate;
  use AC_SYNC\Includes\Classes\Async\Action_Wear_Images_Update as ImagesUpdate;

  class Action_Wear_Core
  {

    protected $utils;
    public static $plugin_dir;
    public static $plugin_file;
    public static $version;
    public static $importProcess;
    public static $importImages;
    public static $differentialProductQueue;
    public static $pricesQueue;

    public function __construct(string $file)
    {
      self::$plugin_dir = dirname($file);
      self::$plugin_file = $file;
      $this->utils = new Utils();
    }

    public function on_plugin_activate()
    {
      $this->installTables();
      $this->createCamacPlaceholder();
      Category::checkDefaultCategoryExist();
      Product::checkDefaultAttributesCreation();
      if (get_option("_ACTIONWEAR_SUPPLIER_AVAILABILITY", false) === false)
        update_option("_ACTIONWEAR_SUPPLIER_AVAILABILITY", 0);
      if (get_option("_ACTIONWEAR_LAST_RESYNCED_PRODUCTS", false) === false)
        update_option("_ACTIONWEAR_LAST_RESYNCED_PRODUCTS", time());
    }

    /**
     * Crea il placeholder di Camac
     *
     * Crea il Placeholder necessario per identificare le immagini prodotto dentro la cartella uploads
     **/
    public function createCamacPlaceholder()
    {
      copy(dirname(__FILE__) . "/camac-placeholder.png", ProductImage::getPixelmagePath());
    }

    public function installTables()
    {
      Action_Wear_Sql_Manager::install();
    }

    public function rp_filter_js_files($file_string)
    {
      return pathinfo($file_string, PATHINFO_EXTENSION) === 'js';
    }

    public function rp_filter_css_files($file_string)
    {
      return pathinfo($file_string, PATHINFO_EXTENSION) === 'css';
    }

    public function rp_load_react_app($hook)
    {

      $is_main_dashboard = $hook === 'toplevel_page_action-wear';
      $is_product_page = $hook === "post.php";


      if ($is_product_page) {
        $id = isset($_GET["post"]) ? sanitize_text_field($_GET["post"]) : 0;

        // validate and cast id to number
        if ((int) $id > 0) {
          if (Product::isCamacProduct($id)) {
            add_action('admin_footer', [$this->utils, 'camac_product_js']);
          }
        }
      }

      if (!$is_main_dashboard)
        return;

      $plugin_app_dir_url = plugin_dir_url(__FILE__) . 'react-code/';
      $react_app_build = $plugin_app_dir_url . 'build/';
      $manifest_url = dirname(__FILE__) . '/react-code/build/asset-manifest.json';
      $request = file_get_contents($manifest_url);

      if (!$request)
        return false;

      $files_data = json_decode($request);
      if ($files_data === null)
        return;

      if (!property_exists($files_data, 'entrypoints'))
        return false;

      $assets_files = $files_data->entrypoints;

      $js_files = array_filter($assets_files, [$this, 'rp_filter_js_files']);
      $css_files = array_filter($assets_files, [$this, 'rp_filter_css_files']);

      foreach ($css_files as $index => $css_file) {
        wp_enqueue_style('react-code-' . $index, $react_app_build . $css_file);
      }

      foreach ($js_files as $index => $js_file) {
        wp_enqueue_script('react-code-' . $index, $react_app_build . $js_file, array(), 1, true);
      }
    }

    public function camac_add_cron_interval($schedules)
    {
      $schedules['camac_sixty_seconds'] = array(
        'interval' => 60,
        'display' => esc_html__('Every 60 Seconds')
      );
      return $schedules;
    }

    public function remove_delete_image($content, $post_id, $thubmnail_id)
    {
      if (Product::isCamacThumbnail($thubmnail_id)) {
        $content = Utils::removeDeleteImageFromString($content);
        $content = Utils::removeClickEventFromSetThumbnail($content);
        $content = Utils::removeHintFromThumbnail($content);
      }
      return $content;
    }

    public function sv_wc_add_order_meta_box_action($actions)
    {
      $actions['wc_custom_order_action'] = "Esporta per ActionWear";
      return $actions;
    }

    public function init_processes()
    {
      Sql::getCurrentVersioningScript();
      self::$importProcess = new ProductImport();
      self::$differentialProductQueue = new DifferentialProductTask();
      self::$pricesQueue = new PricesUpdate();
      self::$importImages = new ImagesUpdate();
    }

    public static function dispatchProcesses()
    {
      if (self::$importProcess->is_active() && !self::$importProcess->is_processing())
        self::$importProcess->dispatch();
      if (self::$differentialProductQueue->is_active() && !self::$differentialProductQueue->is_processing())
        self::$differentialProductQueue->dispatch();
      if (self::$pricesQueue->is_active() && !self::$pricesQueue->is_processing())
        self::$pricesQueue->dispatch();
      if (self::$importImages->is_active() && !self::$importImages->is_processing())
        self::$importImages->dispatch();
    }

    public function camac_cron_exec()
    {
      self::dispatchProcesses();
      Cron::exec();
    }

    public function initialize($version_number)
    {
      self::$version = $version_number;

      // actions
      add_action('admin_menu', [$this->utils, 'actionwear_menu']);
      add_action('admin_enqueue_scripts', [$this, 'rp_load_react_app']);
      add_action('camac_cron_hook', [$this, 'camac_cron_exec']);
      add_action('admin_bar_menu', [$this->utils, 'camac_top_bar'], 999);
      add_action('woocommerce_order_actions', [$this, 'sv_wc_add_order_meta_box_action']);
      add_action('woocommerce_order_action_wc_custom_order_action', [$this->utils, 'sv_wc_process_order_meta_box_action']);
      add_action('woocommerce_before_add_to_cart_button', [$this->utils, 'camac_before_add_to_cart']);
      add_action('plugins_loaded', array($this, 'init_processes'));
      add_action('admin_footer', [$this->utils, 'actionwear_hard_reset_script']);
      add_action('woocommerce_process_product_meta', [$this->utils, 'actionwear_resync_product']);
      add_action('add_meta_boxes', [$this->utils, 'actionwear_product_metabox']);

      // prevent wp errors of php compression
      remove_action('shutdown', 'wp_ob_end_flush_all', 1);
      add_action('shutdown', function () {
        while (@ob_end_flush())
          ;
      });

      // filters
      add_filter('wp_get_attachment_url', [$this->utils, 'attach_cdn_urls'], 10, 2);
      add_filter('cron_schedules', [$this, 'camac_add_cron_interval']);
      add_filter("admin_post_thumbnail_html", [$this, 'remove_delete_image'], 10, 3);
      add_filter('plugin_action_links_' . plugin_basename(self::$plugin_file), [$this->utils, 'actionwear_plugin_action_links']);

      $timestamp = wp_next_scheduled('camac_cron_hook');
      if (!$timestamp) {
        wp_schedule_event(time(), 'camac_sixty_seconds', 'camac_cron_hook');
      }

      // wp_unschedule_event($timestamp, 'camac_cron_hook');

      // execute on activate actions
      register_activation_hook(dirname(__FILE__) . "/actionwear.php", [$this, 'on_plugin_activate']);

      // intiialize api service
      Action_Wear_Api::init();
    }
  }
}
