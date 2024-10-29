<?php

namespace AC_SYNC\Includes\Classes\Sql\Version {
    if (!defined('ABSPATH'))
        exit; // Exit if accessed directly
    class Action_Wear_Sql_Version_1_0_2 extends Action_Wear_Sql_Version_Abstract implements Action_Wear_Sql_Version_Interface
    {

        public static function restoreWcCreated()
        {
            // get all _sku from postmeta that not contain hyphen in meta_value
            global $wpdb;
            $query = "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value NOT LIKE '%-%'";
            $skus = $wpdb->get_col($query);

            // update actionwear_products set wc_created = 1 where sku in (...)
            $query = "UPDATE {$wpdb->prefix}actionwear_products SET wc_created = 1, wc_to_create = 1 WHERE sku IN ('" . implode("','", $skus) . "')";
            $wpdb->query($query);
        }

        public static function isDuplicated($entity, $field)
        {
            global $wpdb;
            $query = "SELECT COUNT(*) FROM {$wpdb->prefix}$entity GROUP BY $field HAVING COUNT(*) > 1";
            $count = (int) $wpdb->get_var($query);
            return $count > 0;
        }

        public static function getDuplicatedIds($entity, $field)
        {
            global $wpdb;
            $query = "SELECT id FROM {$wpdb->prefix}$entity GROUP BY $field HAVING COUNT(*) > 1";
            $ids = $wpdb->get_col($query);
            return $ids;
        }

        public static function removeDuplicates($entity, $field)
        {
            global $wpdb;
            $ids = self::getDuplicatedIds($entity, $field);
            $query = "DELETE FROM {$wpdb->prefix}$entity WHERE id IN ('" . implode("','", $ids) . "')";
            $wpdb->query($query);
        }

        public static function removeDuplicatedData()
        {
            $entities = [
                'actionwear_brands' => 'id_actionwear',
                'actionwear_categories' => 'id_actionwear',
                'actionwear_products' => 'sku',
            ];

            foreach ($entities as $entity => $field) {
                while (self::isDuplicated($entity, $field)) {
                    self::removeDuplicates($entity, $field);
                }
            }
        }

        public static function addIndexes()
        {
            global $wpdb;

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

        public static function versioningScript()
        {
            global $wpdb;

            // remove duplicated data
            self::removeDuplicatedData();

            // restore wc_created
            self::restoreWcCreated();

            // add indexes
            self::addIndexes();
        }
    }
}
