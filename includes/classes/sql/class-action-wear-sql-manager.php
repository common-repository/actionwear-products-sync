<?php

namespace AC_SYNC\Includes\Classes\Sql {
  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
  class Action_Wear_Sql_Manager
  {

    const DB_VERSION = '1.0.1';

    const TABLES = [
      "actionwear_log",
      "actionwear_categories_associations",
      "actionwear_categories_product",
      "actionwear_products",
      "actionwear_brands",
      "actionwear_categories",
      "actionwear_recharges_details",
      "actionwear_recharges",
      "actionwear_images"
    ];

    public static function truncateAll($exclude = [])
    {
      global $wpdb;
      $wpdb->query("SET FOREIGN_KEY_CHECKS = 0;");
      foreach (self::TABLES as $table) {
        if (!in_array($table, $exclude))
          $wpdb->query("TRUNCATE $wpdb->prefix$table");
      }
      $wpdb->query("SET FOREIGN_KEY_CHECKS = 1;");
    }

    public static function addPrefix($table)
    {
      global $wpdb;
      return $wpdb->prefix . $table;
    }

    public static function install()
    {
      global $wpdb;
      $current_db_version = get_option("_ACTIONWEAR_DB_VERSION");

      foreach (self::TABLES as $table) {
        $$table = self::addPrefix($table);
      }

      $wp_terms = $wpdb->prefix . "terms";

      $sql = <<<SQL
        CREATE TABLE {$actionwear_products} (
          `id` INT NOT NULL AUTO_INCREMENT,
          `sku` VARCHAR(64) NOT NULL,
          `cover` VARCHAR(255) NULL DEFAULT NULL,
          `name` VARCHAR(255) NULL DEFAULT NULL,
          `brand_name` VARCHAR(255) NULL DEFAULT NULL,
          `wc_created` BOOLEAN NOT NULL DEFAULT FALSE,
          `wc_to_create` BOOLEAN NOT NULL DEFAULT FALSE,
          `is_configurable` BOOLEAN NOT NULL,
          `last_update` DATETIME NULL DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE = InnoDB;
        CREATE TABLE {$actionwear_log} (
          `id` INT NOT NULL AUTO_INCREMENT,
          `message` TEXT NULL DEFAULT NULL,
          `gravity` TINYINT NOT NULL,
          `context` VARCHAR(255) NULL DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE = InnoDB;
        CREATE TABLE {$actionwear_categories} (
          `id` INT NOT NULL AUTO_INCREMENT,
          `id_actionwear` INT NOT NULL,
          `parent_id_actionwear` INT NOT NULL,
          `name` VARCHAR(255) NOT NULL,
          `is_active` BOOLEAN NOT NULL,
          `product_count` INT NOT NULL,
          `position` INT NOT NULL,
          `level` INT NOT NULL,
          PRIMARY KEY (`id`)) ENGINE = InnoDB;
        CREATE TABLE {$actionwear_brands} (
          `id` INT NOT NULL AUTO_INCREMENT,
          `id_actionwear` INT NOT NULL,
          `name` VARCHAR(255) NOT NULL,
          PRIMARY KEY (`id`)) ENGINE = InnoDB;
        CREATE TABLE {$actionwear_categories_product} (
          `id` INT NOT NULL AUTO_INCREMENT,
          `id_product` INT NOT NULL,
          `id_category` INT NOT NULL,
          PRIMARY KEY (`id`)) ENGINE = InnoDB;
        CREATE TABLE {$actionwear_images} (
          `id` INT NOT NULL AUTO_INCREMENT,
          `sku` VARCHAR(255) NOT NULL,
          `images` TEXT NULL DEFAULT NULL,
          PRIMARY KEY (`id`)) ENGINE = InnoDB;
        CREATE TABLE {$actionwear_recharges} (
          `id` int NOT NULL AUTO_INCREMENT,
          `recharge_type` enum('global','brand','category','') NOT NULL DEFAULT 'global',
          `recharge_entity_id` int NOT NULL,
          `name` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`id`)) ENGINE=InnoDB;
        CREATE TABLE {$actionwear_recharges_details} (
          `id` int NOT NULL AUTO_INCREMENT,
          `id_recharge` int NOT NULL,
          `price_from` float NOT NULL,
          `price_to` float NOT NULL,
          `quantity_from` int NOT NULL,
          `quantity_to` int NOT NULL,
          `percent` int NOT NULL,
          PRIMARY KEY (`id`)) ENGINE=InnoDB;
        CREATE TABLE {$actionwear_categories_associations} (
          `id` INT NOT NULL AUTO_INCREMENT,
          `camac_category_id` INT NOT NULL,
          `wc_category_id` INT NULL DEFAULT NULL,
          `ignore_me` BOOLEAN NOT NULL DEFAULT TRUE,
          PRIMARY KEY (`id`)) ENGINE = InnoDB;
        ALTER TABLE {$actionwear_products} ADD UNIQUE(`sku`);
        ALTER TABLE {$actionwear_brands} ADD UNIQUE(`id_actionwear`);
        ALTER TABLE {$actionwear_categories} ADD INDEX(`id_actionwear`);
        ALTER TABLE {$actionwear_categories} ADD UNIQUE(`id_actionwear`);
SQL;

      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
      $current_version = get_option("_ACTIONWEAR_DB_VERSION");
      if (!$current_version)
        update_option("_ACTIONWEAR_DB_VERSION", self::DB_VERSION);

      $queries = [
        "ALTER TABLE {$wpdb->prefix}actionwear_products ADD UNIQUE(`sku`)",
        "ALTER TABLE {$wpdb->prefix}actionwear_brands ADD UNIQUE(`id_actionwear`)",
        "ALTER TABLE {$wpdb->prefix}actionwear_categories ADD INDEX(`id_actionwear`)",
        "ALTER TABLE {$wpdb->prefix}actionwear_categories ADD UNIQUE(`id_actionwear`)"
      ];

      // force add of unique keys
      foreach ($queries as $query) {
        $wpdb->query($query);
      }

    }
  }
}
