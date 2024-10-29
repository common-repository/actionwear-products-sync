<?php

namespace AC_SYNC\Includes\Classes\Differential\Entities;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Differential\Entities\Action_Wear_Entities as Entities;
use AC_SYNC\Includes\Classes\Differential\Entities\Action_Wear_Entities_Interface as EntitiesInterface;
use AC_SYNC\Includes\Classes\Differential\Schema\Action_Wear_Price_Schema as PriceSchema;
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api as Api;
use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;
use AC_SYNC\Includes\Classes\Product\Action_Wear_Product as Product;

class Action_Wear_Price extends Entities implements EntitiesInterface
{

    const DRY_RUN = false;
    const UPDATE_TIMING = 3600;

    public function getUpdateTiming(): int
    {
        return self::UPDATE_TIMING;
    }

    public function execute(): bool
    {
        if (get_option('_ACTIONWEAR_IS_PRICE_SYNC_DISABLED') == 1)
            return false;

        $date = date("Y-m-d H:i", $this->getExecutionTime());

        $prices = Api::external(
            "GET",
            "/pricesall?compress=0&output=json&from=$date"
        );

        $code = $prices["code"];
        if ($code !== 200) {
            $error_msg = "Errore durante la chiamata al differenziale dei prezzi, restituito codice: " . $code;
            Log::write($error_msg, Log::ERROR, Log::CONTEXT_CAMAC_API);
            throw new \Exception($error_msg);
        }

        $prices = json_decode($prices["raw"]["body"], true)[0];

        if (count($prices) === 0) {
            $this->updateExecutionTime();
            Log::write("Nessun aggiornamento di prezzo trovato nell'arco di tempo che va da $date ad adesso", Log::INFO, Log::CONTEXT_CAMAC_API);
            return false;
        }

        $first_key = array_key_first($prices);
        $this->setApiSchema(array_keys($prices[$first_key]));

        if (!$this->conformToSchema(new PriceSchema)) {
            $error_msg = "Lo schema dei prezzi ricevuti non è conforme a quello atteso";
            Log::write($error_msg, Log::ERROR, Log::CONTEXT_CAMAC_API);
            return false;
        }

        $processed = 0;
        foreach ($prices as $price) {
            $sku_simple = $price["sku"];
            $sku_conf = $price["sku_camac"];
            $prezzo = (float) str_replace(",", ".", $price["prezzo unita"]);

            // skippa se il configurabile non esiste
            $id_conf = wc_get_product_id_by_sku($sku_conf);
            if ($id_conf === 0)
                continue;

            // skippa se il semplice non esiste
            $id_simple = wc_get_product_id_by_sku($sku_simple);
            if ($id_simple === 0)
                continue;

            $processed++;

            $variation = new \WC_Product_Variation($id_simple);

            // delete variation if price is 0
            if ($prezzo === 0) {
                Log::write("Elimino la variante del prodotto $sku_simple", Log::INFO, Log::CONTEXT_PRODUCT_UPDATE);

                $variation->delete(true);
                continue;
            }

            $conf = new \WC_Product_Variable($id_conf);

            $regular_price = $variation->get_regular_price();

            if (self::DRY_RUN) {
                Log::write("Aggiorno il prezzo del prodotto $sku_simple, prezzo originale: $regular_price, prezzo ricevuto: $prezzo, prezzo con ricarico: " . Product::getRechargedPrice($conf, $prezzo), Log::INFO, Log::CONTEXT_PRODUCT_UPDATE);
                continue;
            }

            $variation->set_regular_price(Product::getRechargedPrice($conf, $prezzo));
            $variation->save();
        }

        Log::write("Ho aggiornato i prezzi da $date ad adesso, " . count($prices) . " record presenti e $processed record aggiornati", Log::INFO, Log::CONTEXT_CAMAC_API);

        $this->updateExecutionTime();
        return true;
    }
}
