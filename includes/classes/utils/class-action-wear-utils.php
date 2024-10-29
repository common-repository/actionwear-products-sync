<?php

namespace AC_SYNC\Includes\Classes\Utils {
  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
  use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
  use AC_SYNC\Includes\Classes\Sync\Action_Wear_Queue as Queue;
  use AC_SYNC\Includes\Classes\Utils\Action_Wear_Circuit_Breaker as CircuitBreaker;

  class Action_Wear_Utils
  {

    public static function checkHtaccessRules()
    {
      $htaccess = file_get_contents(ABSPATH . ".htaccess");
      preg_match_all('/### BEGIN ACTIONWEAR - DO NOT EDIT ###/im', $htaccess, $results);
      if (empty($results[0])) {
        self::createHtaccessRules($htaccess);
      }
    }

    public static function createHtaccessRules($htaccess)
    {
      $addRule = "### BEGIN ACTIONWEAR - DO NOT EDIT ###
  RewriteEngine On
  RewriteRule ^wp-admin/actionwear-app$ wp-admin/admin.php?page=action-wear [L]
  RewriteRule ^wp-admin/actionwear-app/(.*)$ wp-admin/admin.php?page=action-wear [L]
### END ACTIONWEAR ###


";
      file_put_contents(ABSPATH . ".htaccess", $addRule . $htaccess);
    }

    public function action_wear_page()
    {
      include \AC_SYNC\Action_Wear_Core::$plugin_dir . "/views/admin/app.php";
    }

    public function actionwear_menu()
    {
      $icon_base64 = "data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAyMy4wLjEsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGl2ZWxsb18xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCINCgkgdmlld0JveD0iMCAwIDM2IDM2IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAzNiAzNjsiIHhtbDpzcGFjZT0icHJlc2VydmUiPg0KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4NCgkuc3Qwe2ZpbGw6I0E3QUFBRDt9DQo8L3N0eWxlPg0KPHBhdGggY2xhc3M9InN0MCIgZD0iTTExLjM1LDI4LjE0Yy0wLjU1LDAuNDEtMS4yOCwwLjczLTIuMTcsMC45OGMtMC44OSwwLjIzLTEuOTcsMC4zNy0zLjI1LDAuMzdjLTAuOTgsMC0xLjg1LTAuMTYtMi42MS0wLjQ2DQoJYy0wLjczLTAuMzItMS4zNS0wLjczLTEuODUtMS4yNnMtMC44Ny0xLjE3LTEuMS0xLjkyQzAuMTQsMjUuMTIsMCwyNC4zMiwwLDIzLjQ4YzAtMS4xNCwwLjE4LTIuMSwwLjU3LTIuOTMNCgljMC4zOS0wLjgyLDAuODktMS41MSwxLjU2LTIuMDhjMC42Ni0wLjU1LDEuNDQtMC45OCwyLjM2LTEuM2MwLjkyLTAuMzIsMS44OC0wLjU1LDIuOTMtMC42OXYtMC43M2MwLTEuNjUtMC43Ni0yLjQ3LTIuMjktMi40Nw0KCWMtMC42NiwwLTEuMywwLjA5LTEuOTcsMC4yN2MtMC42NCwwLjE4LTEuMTksMC4zOS0xLjYyLDAuNjJsLTAuODctMi44OGMwLjQ4LTAuMywxLjE3LTAuNTcsMi4wOC0wLjgyDQoJYzAuOTItMC4yNywxLjk0LTAuNDEsMy4xMS0wLjQxYzEuNzIsMCwzLjA0LDAuNSw0LDEuNDlzMS40NCwyLjUyLDEuNDQsNC41OHYxMi4wM0gxMS4zNXogTTcuNDQsMTkuMDgNCgljLTEuMTQsMC4xNi0yLjA2LDAuNTctMi43LDEuMTlzLTAuOTYsMS42LTAuOTYsMi44OGMwLDAuOTQsMC4xOCwxLjcyLDAuNTcsMi4zM2MwLjM5LDAuNjIsMC45NiwwLjk0LDEuNzQsMC45NA0KCWMwLjMsMCwwLjU1LTAuMDIsMC43OC0wLjA5YzAuMjMtMC4wNywwLjQxLTAuMTQsMC41NS0wLjIxdi03LjA1SDcuNDR6Ii8+DQo8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTAuMzMsMTBjMCwwLDIuNDMsMC45MiwyLjQzLDQuMjZjMCwxLjE0LDAsMTUuMjgsMCwxNS4yOGgyMy4yMUwzNS45MywxMEgxMC4zM3ogTTMxLjE3LDI4LjNoLTMuMzJMMjYsMTkuNDUNCgljLTAuMDctMC4yNy0wLjE0LTAuNTctMC4xNi0wLjgyYy0wLjA1LTAuMjctMC4wNy0wLjUzLTAuMTEtMC43NmMtMC4wNS0wLjI3LTAuMDctMC41My0wLjA5LTAuNzZoLTAuMDUNCgljLTAuMDIsMC4yMy0wLjA1LDAuNDgtMC4wOSwwLjczYy0wLjA1LDAuMjMtMC4wNywwLjQ4LTAuMTEsMC43NmMtMC4wNSwwLjI3LTAuMDksMC41Ny0wLjE0LDAuODVsLTEuODgsOC44NWgtMy4zNGwtMy41Mi0xNy4yNWgzLjU3DQoJbDEuNjUsOS44MmMwLjA1LDAuMjcsMC4wNywwLjU3LDAuMTEsMC44NWMwLjA1LDAuMjcsMC4wNywwLjUzLDAuMTEsMC43NmMwLjA1LDAuMjcsMC4wNywwLjUzLDAuMDksMC43NmgwLjA1DQoJYzAuMDItMC4yNSwwLjA1LTAuNSwwLjA5LTAuNzZjMC4wNS0wLjIzLDAuMDctMC40OCwwLjExLTAuNzZzMC4wOS0wLjU3LDAuMTQtMC44NWwxLjkyLTkuODJoMi44OGwxLjksOS44Mg0KCWMwLjA1LDAuMywwLjExLDAuNTksMC4xNCwwLjg3YzAuMDUsMC4yNywwLjA3LDAuNTMsMC4xMSwwLjc2YzAuMDUsMC4yNywwLjA3LDAuNTMsMC4wOSwwLjc2aDAuMDVjMC0wLjI1LDAuMDItMC41LDAuMDUtMC43Ng0KCWMwLjA1LTAuMjMsMC4wNy0wLjQ4LDAuMDktMC43NnMwLjA3LTAuNTcsMC4xNC0wLjg3bDEuNjktOS44MmgzLjI1TDMxLjE3LDI4LjN6Ii8+DQo8L3N2Zz4NCg==";
      add_menu_page('ActionWear', 'ActionWear', 'manage_options', 'action-wear', [$this, 'action_wear_page'], $icon_base64);
    }

    public static function isJson($s)
    {
      json_decode($s);
      return json_last_error() === JSON_ERROR_NONE;
    }

    public function actionwear_hard_reset_script()
    {
      $site_url = get_rest_url();
      echo '
      <script type="text/javascript">
        function actionwear_hard_reset(){
          if(confirm("Are you sure? This action is irreversible.\nUse Hard reset only if you got a configuration error that don\'t let you to continue and go over.")){
            jQuery.ajax({
              method: "GET",
              url: "' . esc_url($site_url) . 'actionwear-api/v1/reset_configuration",
              success: (r) => {
                alert("Reset completed.");
                location.reload(true);
              } 
            })
          }
        }
      </script>
      ';
    }

    // Add custom links to the plugin action links
    public function actionwear_plugin_action_links($links)
    {
      $custom_links = array(
        '<a href="' . esc_url(admin_url('admin.php?page=action-wear')) . '">Settings</a>',
        '<a style="color:red" href="javascript:actionwear_hard_reset();">Hard reset</a>'
      );

      // Merge the custom links with existing action links
      return array_merge($links, $custom_links);
    }

    public static function removeDeleteImageFromString($c)
    {
      $is_image_customization_enabled = get_option('_ACTIONWEAR_IMAGES_CUSTOMIZATION');
      if ($is_image_customization_enabled === "1")
        return $c;
      return preg_replace('/(<a href="#" id="remove-post-thumbnail">(.*)<\/a>)/', "", $c);
    }

    public static function removeClickEventFromSetThumbnail($c)
    {
      $is_image_customization_enabled = get_option('_ACTIONWEAR_IMAGES_CUSTOMIZATION');
      if ($is_image_customization_enabled === "1")
        return $c;
      return str_replace('class="thickbox"', 'style="pointer-events:none;" class="thickbox"', $c);
    }

    public static function removeHintFromThumbnail($c)
    {
      $is_image_customization_enabled = get_option('_ACTIONWEAR_IMAGES_CUSTOMIZATION');
      if ($is_image_customization_enabled === "1")
        return $c;
      return preg_replace('/(<p class="hide-if-no-js howto" id="set-post-thumbnail-desc">(.*)<\/p>)/', "", $c, 1);
    }
    /**
     * Verifica se il cronjob può essere eseguito in base a dei controlli, per es: l'onboarding è completato
     *
     * @return boolean
     **/
    public static function cronjobCanRun()
    {
      $onboarding = (int) get_option("_ACTIONWEAR_ONBOARDING");
      $progress = (int) get_option("_ACTIONWEAR_INITIAL_SYNC_PROGRESS");
      $cronjobFrozen = (int) get_option("_ACTIONWEAR_CRONJOB_FROZEN");
      return $onboarding === 4 && $progress === 100 && !$cronjobFrozen;
    }

    public static function convertMemoryValues($memory_limit)
    {
      if (strpos($memory_limit, 'G')) {
        return $memory_limit;
      } else {
        $memory_value = substr($memory_limit, 0, -1);
        $memory_value = ($memory_value / 1024) . 'G';
      }
      return $memory_value;
    }

    public static function generateCsvOrder($order)
    {
      $rows = [];
      foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        $id = $product->get_id();
        $is_camac_product = get_post_meta($id, 'quantity_supplier', true);
        if ($is_camac_product === "")
          continue;
        $sku = $product->get_sku();
        $sku = explode("-", $sku);
        $rows[] = $sku[0] . "," . $sku[1] . "," . $sku[2] . "," . $item->get_quantity();
      }
      return implode("\n", $rows);
    }

    public function camac_product_js()
    {
      $is_image_customization_enabled = get_option('_ACTIONWEAR_IMAGES_CUSTOMIZATION');
      if ($is_image_customization_enabled === "1")
        return;

      echo '
        <script type="text/javascript">
          jQuery(".add_product_images").remove();
          jQuery(".product_images .delete.tips").remove();
        </script>
      ';
    }

    public function camac_product_js_updater()
    {
      $site_url = get_rest_url();
      echo '
        <script type="text/javascript">
          ($ => {
            const camac_updater = setInterval(() => {
              $.ajax({
                method: "GET",
                url: "' . esc_url($site_url) . 'actionwear-api/v1/get_login_status",
                success: (r) => {
                  const method = r.data.circuitOpen ? "show" : "hide";
                  $("#apiCircuitError")[method](0);
                  $(".camac_queue_count").text(r.data.queueCount);
                  if(r.data.queueCount === 0){
                    $("#wp-admin-bar-camac-progress").remove();
                  }
                } 
              })
          }, 7000)
          })(jQuery);
        </script>
      ';
    }

    public function getSizeByScreen($screen)
    {
      $id_to_size = [
        "edit-product" => 150,
        "product" => 300
      ];
      return array_key_exists($screen->id, $id_to_size) ? $id_to_size[$screen->id] : null;
    }

    public function getSizeByWpQuery()
    {
      global $wp_query;
      $size = "null";
      $context_to_size = [
        "cart" => 300,
        "listing" => 600
      ];
      $content = $wp_query->queried_object->post_content ?? "";
      if (!empty($content)) {
        $size = strpos($content, "[woocommerce_cart]") !== false ? $context_to_size["cart"] : "null";
      } else {
        $size = (isset($wp_query->query["post_type"]) && $wp_query->query["post_type"] === "product") ? $context_to_size["listing"] : "null";
      }

      return $size;
    }

    /**
     * Hook that replace media_url if is Camac product
     *
     * Search in the post_metas to check if is camac_product, then get the right media url
     *
     * @return string $url
     **/
    public function attach_cdn_urls($url, $id)
    {
      global $current_screen;

      // initialize size as null so the default is the original image size
      $size = "null";

      $size = $this->getSizeByWpQuery();

      if ($current_screen !== null) {
        $size = $this->getSizeByScreen($current_screen);
      }

      $camac_product_id = (int) get_post_meta($id, "camac_product_id", true);
      if (!$camac_product_id)
        return $url;

      $cdn_urls = get_post_meta($camac_product_id, "camac_cdn_urls", true);
      if ($cdn_urls === false || $cdn_urls === "")
        return $url;
      $cdn_urls = json_decode($cdn_urls);

      $gallery_ids = explode(",", get_post_meta($camac_product_id, "camac_attachment_ids", true));
      if (!in_array($id, $gallery_ids))
        return $url;

      $sku = get_post_meta($id, "camac_product_sku", true);
      if (isset($cdn_urls->{$sku})) {
        $position = 0;
        $galleries = get_post_meta($camac_product_id, "_product_image_gallery", true);
        $galleries = explode(",", $galleries);
        if (in_array($id, $galleries)) {
          $position = array_search($id, $galleries);
        }
        $single_url = $cdn_urls->{$sku}[$position];
        // force cdn use
        $single_url = str_replace("://action-wear.com", "://media.action-wear.com", $single_url);
        $url = $single_url . "?iswp=true/&ignore=true&size=$size";
        return $url;
      }
      return $url;
    }

    public function camac_before_add_to_cart()
    {
      global $product, $wpdb;
      $id = $product->get_id();
      if (!Product::isCamacProduct($id))
        return;
      if (get_option("_ACTIONWEAR_SUPPLIER_AVAILABILITY") !== "1")
        return;
      $supplier_setting = get_option("_ACTIONWEAR_SUPPLIER_TYPE", "simple");
      wp_enqueue_script('actionwear-frontend-script', plugins_url('/js/actionwear-front.js', realpath(__DIR__ . "/../../")));
      wp_enqueue_style('actionwear-frontend-style', plugins_url('/css/style.css', realpath(__DIR__ . "/../../")));
      $childrens = $product->get_children();
      $quantities_details = Product::getQuantitiesDetails($childrens);
      $quantities_details = json_encode($quantities_details);
      $sku = $product->get_sku();
      $sku = (string) $sku;
      $brand = $wpdb->get_var("SELECT brand_name FROM {$wpdb->prefix}actionwear_products WHERE sku = '$sku'");
      echo "
      <div id='actionwear_quantities' class='actionwear-frontend'></div>
      <script>
      const _ACTIONWEAR = {
          product_quantities_info: JSON.parse('$quantities_details'),
          product_quantities_view_type: '" . esc_js($supplier_setting) . "',
          brand : '" . esc_js($brand) . "'
        };
      </script>
      ";
    }

    public function actionwear_resync_product()
    {
      if (isset($_POST['_ac_action']) && $_POST['_ac_action'] == "actionwear_update_request") {
        // Verify nonce for security
        if (!isset($_POST['actionwear_product_metabox_nonce_field']) || !wp_verify_nonce($_POST['actionwear_product_metabox_nonce_field'], 'actionwear_product_metabox_nonce')) {
          wp_die('Security check failed.');
        }

        $product_id = isset($_POST['post_id']) ? $_POST['post_id'] : 0;

        $sku = get_post_meta($product_id, "_sku", true);
        if (empty($sku))
          return;
        $product = Product::getRemoteProductsListino([$sku]);
        \AC_SYNC\Action_Wear_Core::$importProcess->push_to_queue($product->{$sku});
        \AC_SYNC\Action_Wear_Core::$importProcess->save();

        // Redirect back to the product edit page after execution
        wp_redirect(admin_url('post.php?post=' . $product_id . '&action=edit&product_update_requested=' . rand(0, 9999)));
        exit;
      }
    }

    public function actionwear_product_metabox()
    {
      $id = isset($_GET["post"]) ? $_GET["post"] : 0;
      $id = (int) $id;
      if (!Product::isCamacProduct($id))
        return;
      add_meta_box(
        'actionwear_product_metabox',
        'Actionwear',
        [$this, 'actionwear_render_product_metabox'],
        'product',
        'side',
        'high'
      );
    }

    public function actionwear_render_product_metabox($post)
    {

      $update_requested = isset($_GET["product_update_requested"]) ? true : false;
      $update_requested_html = $update_requested ? '<div class="notice notice-success is-dismissible"><p><b>ACTIONWEAR</b>: Update requested has been added to queue successfully.</p></div>' : '';
      // Output HTML for the metabox
      $qs = "document.querySelector('input[name=_ac_action]').value = 'actionwear_update_request';";
      echo '
      <div class="custom-metabox">
        ' . $update_requested_html . '
        <p>Update product informations with fresh data</p>
        <form method="POST" action="">
          <input type="hidden" name="_ac_action" value="">
          <input type="hidden" name="post_id" value="' . $post->ID . '">
          ' . wp_nonce_field('actionwear_product_metabox_nonce', 'actionwear_product_metabox_nonce_field') . '
          <button type="submit" class="button button-hero" onclick="' . $qs . '">Get fresh data</button>
        </form>
      </div>
      ';
    }

    public function camac_top_bar($wp_admin_bar)
    {
      $queue = Queue::getQueue();
      $total = count($queue);
      $isProcessingQueue = $total > 0;
      $isOpen = (int) get_option(CircuitBreaker::CACHE_KEY, 0) === 3;
      $title_html = '
      <div style="display:flex; justify-content:center; align-items:center">
        <div style="display:flex;">
          <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAcklEQVQ4T2NkoDJgJMe8PfraN10uXlXHphfFQCZm1v/oiv79/c0PFPsEE9+jp42hxuXSVbg5jNgMwWbzLm01ojwzBAwE+YMYbxPtZWoaCIocjGSDy7W4XIgcwyDHjRqIO8ZHwxCer0eTDWZOIarQw6MIABzARWWWT9tHAAAAAElFTkSuQmCC" />
        </div>
        <div style="margin-left:10px">
          Actionwear are processing task queue.. <span class="camac_queue_count">' . $total . '</span> remaining
        </div>
      </div>';
      if ($isProcessingQueue) {
        $args = array(
          'id' => 'camac-progress',
          'title' => $title_html,
          'href' => get_site_url() . '/wp-admin/admin.php?page=action-wear',
        );
        $wp_admin_bar->add_node($args);
      }
      $showClass = $isOpen ? "flex" : "none";
      $circuit_html = '
      <div style="display:' . $showClass . '; justify-content:center; align-items:center; background: #ff4444;" id="apiCircuitError">
        <div style="display:flex;">
         <svg style="width:24px; height:24px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
          </svg>
        </div>
        <div style="margin-left:10px">
          Actionwear API is not available at the moment, you may encounter some issues.
        </div>
      </div>';
      $args = array(
        'id' => 'camac-circuit',
        'title' => $circuit_html,
        'href' => get_site_url() . '/wp-admin/admin.php?page=action-wear',
      );
      $wp_admin_bar->add_node($args);
      add_action('admin_footer', [$this, 'camac_product_js_updater']);
    }

    public function sv_wc_process_order_meta_box_action($order)
    {
      $message = sprintf('Order exported by %s', wp_get_current_user()->display_name);
      $order->add_order_note($message);
      header("Content-Type: application/octet-stream");
      $name = time() . "_" . $order->get_id() . ".csv";
      header("Content-Disposition: attachment; filename=$name");
      header("Pragma: no-cache");
      header("Expires: 0");
      $content = self::generateCsvOrder($order);
      die($content);
    }
  }
}
