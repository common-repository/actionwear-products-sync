<?php

namespace AC_SYNC\Includes\Classes\Differential\Entities;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Differential\Entities\Action_Wear_Entities as Entities;
use AC_SYNC\Includes\Classes\Differential\Entities\Action_Wear_Entities_Interface as EntitiesInterface;
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api as Api;
use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
use AC_SYNC\Includes\Classes\Differential\Schema\Action_Wear_Quantity_Schema as QuantitySchema;

class Action_Wear_Quantity extends Entities implements EntitiesInterface
{

    const DRY_RUN = false;
    const UPDATE_TIMING = 300;

    public function getUpdateTiming(): int
    {
        return self::UPDATE_TIMING;
    }

    public function execute(): bool
    {
        $date = date("Y-m-d H:i", $this->getExecutionTime());

        $quantities = Api::external(
            "GET",
            "/stockall?compress=0&output=json&from=$date"
        );

        $code = $quantities["code"];
        if ($code !== 200) {
            $error_msg = "Errore durante la chiamata al differenziale delle quantità, restituito codice: " . $code;
            Log::write($error_msg, Log::ERROR, Log::CONTEXT_CAMAC_API);
            throw new \Exception($error_msg);
        }

        $quantities = json_decode($quantities["raw"]["body"], true)[0];

        if (count($quantities) === 0) {
            $this->updateExecutionTime();
            Log::write("Nessun aggiornamento di quantità trovato nell'arco di tempo che va da $date ad adesso", Log::INFO, Log::CONTEXT_CAMAC_API);
            return false;
        }

        $first_key = array_key_first($quantities);

        $this->setApiSchema(array_keys($quantities[$first_key]));
        if (!$this->conformToSchema(new QuantitySchema)) {
            Log::write("Schema non conforme", Log::ERROR, Log::CONTEXT_CAMAC_API);
            return false;
        }

        $processed = 0;
        foreach ($quantities as $quantity) {
            $sku = $quantity["sku"];
            $id = wc_get_product_id_by_sku($sku);
            if ($id === 0)
                continue;
            $processed++;
            $qty_available = (float) $quantity["available"];
            $qty_supplier = (float) $quantity["supplier"];
            $qty_total_arrivals = (float) $quantity["total_arrivals"];
            if (self::DRY_RUN) {
                Log::write("Aggiorno la quantità del prodotto $sku, quantità disponibile: $qty_available, quantità fornitore: $qty_supplier, quantità total_arrivals: $qty_total_arrivals", Log::INFO, Log::CONTEXT_PRODUCT_UPDATE);
                continue;
            }
            $variation = new \WC_Product_Variation($id);
            $variation->set_stock_quantity($qty_available);
            update_post_meta($id, "quantity_supplier", $qty_supplier);
            update_post_meta($id, "total_arrivals", $qty_total_arrivals);
            $variation->save();
        }

        Log::write("Ho aggiornato le quantità da $date ad adesso, " . count($quantities) . " record presenti e $processed record aggiornati", Log::INFO, Log::CONTEXT_CAMAC_API);

        $this->updateExecutionTime();
        return true;
    }
}
