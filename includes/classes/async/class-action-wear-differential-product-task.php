<?php

namespace AC_SYNC\Includes\Classes\Async;

use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;
use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;

class Action_Wear_Differential_Product_Task extends Action_Wear_Background_Process
{

    /**
     * @var string
     */
    protected $prefix = 'actionwear';

    /**
     * @var string
     */
    protected $action = 'differential_product_task';

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
            // check if Product has static method of $item value
            if (!method_exists(Product::class, $item)) {
                $error = "Method $item does not exist in " . Product::class;
                Log::write($error, Log::ERROR, Log::CONTEXT_CRONJOB, __LINE__, __FILE__);
                throw new \Exception($error);
            }
            Product::$item();
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
