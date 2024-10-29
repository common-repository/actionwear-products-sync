<?php

namespace AC_SYNC\Includes\Classes\Async;

use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
use AC_SYNC\Includes\Classes\Price\Action_Wear_Price_Recharger as PriceRecharger;

class Action_Wear_Prices_Update extends Action_Wear_Background_Process
{

    /**
     * @var string
     */
    protected $prefix = 'actionwear';

    /**
     * @var string
     */
    protected $action = 'prices_update';

    /**
     * Perform task with queued item.
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item Queue item to iterate over.
     *
     * @return mixed
     */
    protected function task($item)
    {

        try {
            $_product = new \WC_Product_Variable(wc_get_product_id_by_sku($item));
            // get product variations
            $variations = $_product->get_available_variations();
            foreach ($variations as $variation) {
                $variation_id = $variation['variation_id'];
                $variation_obj = new \WC_Product_Variation($variation_id);
                // get original_base_price postmeta
                $original_base_price = get_post_meta($variation_id, 'original_base_price', true);
                $brand_id = get_post_meta($variation_id, 'brand_id', true);
                $cat_ids = json_decode(get_post_meta($variation_id, 'cat_ids', true)) ?? [];
                $price_recharger = new PriceRecharger($original_base_price);
                $price = $price_recharger->getPrice((int) $brand_id, $cat_ids);
                if (!empty($price)) {
                    $variation_obj->set_regular_price($price);
                    $variation_obj->save();
                }
            }
        } catch (\Throwable $e) {
            Log::write($e->getMessage(), Log::ERROR, Log::CONTEXT_CRONJOB, __LINE__, __FILE__);
        }
        return false;
    }

    /**
     * Complete processing.
     *
     * Override if applicable, but ensure that the below actions are
     * performed, or, call parent::complete().
     */
    protected function complete()
    {
        parent::complete();

        // Show notice to user or perform some other arbitrary task...
    }
}
