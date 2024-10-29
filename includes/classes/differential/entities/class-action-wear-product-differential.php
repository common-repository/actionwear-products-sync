<?php

namespace AC_SYNC\Includes\Classes\Differential\Entities;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Differential\Entities\Action_Wear_Entities as Entities;
use AC_SYNC\Includes\Classes\Differential\Entities\Action_Wear_Entities_Interface as EntitiesInterface;
use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
use AC_SYNC\Action_Wear_Core as Core;

class Action_Wear_Product_Differential extends Entities implements EntitiesInterface
{

    const DRY_RUN = false;
    const UPDATE_TIMING = 3600 * 36;

    public function getUpdateTiming(): int
    {
        return self::UPDATE_TIMING;
    }

    public function execute(): bool
    {
        global $wpdb;
        try {
            $wpdb->query("TRUNCATE " . $wpdb->prefix . "actionwear_images");
            $wpdb->query("TRUNCATE " . $wpdb->prefix . "actionwear_categories_product");
            Product::filterDeletedProducts();
            Core::$differentialProductQueue->push_to_queue('generateCategories');
            Core::$differentialProductQueue->push_to_queue('generateProducts');
            Core::$differentialProductQueue->push_to_queue('generateImages');
            Core::$differentialProductQueue->save();
            Log::write("Aggiunti gli aggiornamenti di brands, categorie, prodotti ed immagini alla coda", Log::INFO, Log::CONTEXT_CRONJOB);
        } catch (\Exception $e) {
            Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_PRODUCT_CREATION);
        }
        $this->updateExecutionTime();
        return true;
    }
}
