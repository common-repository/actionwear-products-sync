<?php

namespace AC_SYNC\Includes\Classes\Product {
  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
  require_once ABSPATH . 'wp-admin/includes/image.php';
  require_once ABSPATH . 'wp-admin/includes/file.php';
  require_once ABSPATH . 'wp-admin/includes/media.php';

  class Action_Wear_Product_Image
  {

    private $images;
    private $sku;
    private $placeholders = [];
    private $productId;

    public function __construct(string $sku, int $productId)
    {
      $this->sku = $sku;
      $this->productId = $productId;
    }

    public function addPlaceholder($id)
    {
      $this->placeholders[] = $id;
    }

    public static function getPixelBase64()
    {
      return "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAFhAJ/wlseKgAAAABJRU5ErkJggg==";
    }

    public static function getPixelmagePath()
    {
      $wp_upload_dir = wp_upload_dir();
      $dir = $wp_upload_dir["base_dir"] ?? "";
      if (empty($dir))
        $dir = WP_CONTENT_DIR . "/uploads";
      return $dir . "/camac-placeholder.png";
    }

    public function createPlaceHolder($sku)
    {
      global $wpdb;
      $metadata = [
        "width" => 1200,
        "height" => 1600,
        "file" => $this->getPixelmagePath(),
        "sizes" => [],
        'image_meta' =>
          [
            'aperture' => '0',
            'credit' => '',
            'camera' => '',
            'caption' => '',
            'created_timestamp' => '0',
            'copyright' => '',
            'focal_length' => '0',
            'iso' => '0',
            'shutter_speed' => '0',
            'title' => '',
            'orientation' => '0',
            'keywords' =>
              [],
          ]
      ];
      $slug = "placeholder-image-$sku-";
      $slug_exist = $wpdb->get_var("SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name LIKE '$slug%' ORDER BY ID DESC");
      if ($slug_exist !== NULL) {
        $slug_parts = explode("-", $slug_exist);
        $num = (int) end($slug_parts);
        $slug .= $num + 1;
      } else {
        $slug .= "0";
      }
      $post_data = [
        "post_author" => 0,
        "post_content" => $sku,
        "post_title" => $sku,
        "post_excerpt" => "",
        "post_date" => date("Y-m-d H:i:s"),
        "post_date_gmt" => gmdate("Y-m-d H:i:s"),
        "post_modified" => date("Y-m-d H:i:s"),
        "post_modified_gmt" => gmdate("Y-m-d H:i:s"),
        "post_status" => "inherit",
        "comment_status" => "open",
        "ping_status" => "closed",
        "post_name" => $slug,
        "post_parent" => $this->getProductId(),
        "guid" => "/",
        "post_type" => "attachment",
        "post_mime_type" => "image/png"
      ];
      $post_creation = $wpdb->insert(
        $wpdb->prefix . "posts",
        $post_data
      );
      $id = $wpdb->insert_id;
      update_post_meta($id, "_wp_attachment_metadata", $metadata);
      update_post_meta($id, "_wp_attached_file", $this->getPixelmagePath());
      update_post_meta($id, "camac_product_id", $this->getProductId());
      update_post_meta($id, "camac_product_sku", $sku);
      return $id;
    }

    /**
     * Create images placeholder starting from given urls and associate to given sku
     *
     * NOTE: Temporary disabled multi simple images because WC doesn't manage multiples
     *
     * @param string $sku Sku string of simple product
     * @param array $urls Array of urls
     * @return array An array containing generated ids
     **/
    public function setSimpleImagesBySku(string $sku, array $urls): array
    {
      $ids = [];
      $urls = [$urls[array_keys($urls)[0]]]; // constraint to manage only first url
      foreach ($urls as $url) {
        $id = $this->createPlaceHolder($sku);
        $this->addPlaceholder($id);
        $ids[] = $id;
      }
      $this->images[$sku] = $urls;
      return $ids;
    }

    /**
     * Create images placeholder starting from given urls and associate to current object sku
     *
     * @param array $urls Array of urls
     * @return array An array containing generated ids
     **/
    public function setConfigurableImages($urls): array
    {
      $ids = [];
      foreach ($urls as $url) {
        $id = $this->createPlaceHolder($this->sku);
        $this->addPlaceholder($id);
        $ids[] = $id;
      }
      $this->images[$this->sku] = $urls;
      return $ids;
    }

    public static function getOrphanImages()
    {
      global $wpdb;
      $ids = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND post_parent = 0");
      return $ids;
    }

    public static function getImagesBySku($sku)
    {
      global $wpdb;
      $temp_sku = explode("-", $sku);
      $after_where = "= '$sku'";
      if (count($temp_sku) > 2) {
        $sku = $temp_sku[0] . "-" . $temp_sku[1];
        $after_where = "LIKE '$sku%'";
      }
      $images = $wpdb->get_var("SELECT images FROM {$wpdb->prefix}actionwear_images WHERE sku $after_where");
      if (!empty($images)) {
        $images = json_decode($images);
        return $images;
      }
      return [];
    }

    public function getCover()
    {
      return $this->images[$this->sku][0];
    }

    public function getImageMetas()
    {
      return json_encode($this->images);
    }

    public function saveToMeta()
    {
      update_post_meta($this->getProductId(), "camac_cdn_urls", $this->getImageMetas());
      update_post_meta($this->getProductId(), "camac_attachment_ids", implode(",", $this->placeholders));
    }

    /**
     * Get the value of productId
     */
    public function getProductId()
    {
      return $this->productId;
    }

    /**
     * Set the value of productId
     *
     * @return  self
     */
    public function setProductId($productId)
    {
      $this->productId = $productId;

      return $this;
    }
  }
}
