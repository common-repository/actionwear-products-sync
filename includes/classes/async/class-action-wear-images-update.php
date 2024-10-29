<?php

namespace AC_SYNC\Includes\Classes\Async;

use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
use AC_SYNC\Includes\Classes\Setting\Action_Wear_Setting_Debug_Mode as DebugMode;
use AC_SYNC\Includes\Classes\Product\Action_Wear_Product_Image as ProductImage;
class Action_Wear_Images_Update extends Action_Wear_Background_Process
{

    /**
     * @var string
     */
    protected $prefix = 'actionwear';

    /**
     * @var string
     */
    protected $action = 'images_update';

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
            $productImage = new ProductImage($item, $_product->get_id());
            $configurable_images = ProductImage::getImagesBySku($item);
            $image_ids = $productImage->setConfigurableImages($configurable_images);
            $cover_id = false;
            if (DebugMode::getDebugMode())
                Log::write("Trovate le immagini del configurabile: " . implode(",", $configurable_images), Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
            if (count($image_ids) > 0)
                $cover_id = $image_ids[0];
            if ($cover_id !== false)
                $_product->set_image_id($cover_id);
            $_product->set_gallery_image_ids($image_ids);
            $_product->save();
            if (DebugMode::getDebugMode())
                Log::write("Acquisite e salvate immagini configurabile per sku $item", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);

            $variations = $_product->get_available_variations();
            foreach ($variations as $variation) {
                $variation_id = $variation['variation_id'];
                $variation_obj = new \WC_Product_Variation($variation_id);
                $variation_sku = $variation_obj->get_sku();
                $cover = false;

                $simple_images = ProductImage::getImagesBySku($variation_sku);
                if (count($simple_images) > 0) {
                    $image_ids = $productImage->setSimpleImagesBySku($variation_sku, $simple_images);
                    if (DebugMode::getDebugMode())
                        Log::write("Impostate le immagini " . implode(",", $image_ids) . " del semplice con sku $variation_sku", Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
                    if (count($image_ids) > 0) {
                        $cover = $image_ids[0];
                    }
                }
                if ($cover !== false)
                    $variation_obj->set_image_id($cover);
                $variation_obj->save();
            }
            $productImage->saveToMeta();
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
