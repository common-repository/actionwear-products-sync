<?php

namespace AC_SYNC\Includes\Classes\Async;

use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
use stdClass;

class Action_Wear_Product_Import extends Action_Wear_Background_Process
{

    /**
     * @var string
     */
    protected $prefix = 'actionwear';

    /**
     * @var string
     */
    protected $action = 'product_import';

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
        @set_time_limit(300);
        try {
            // if is not requested from api, get data from api
            if (!($item instanceof stdClass))
                $item = Product::getRemoteProductsListino([$item])->{$item};
            Product::createOrUpdateWcProduct($item);
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
