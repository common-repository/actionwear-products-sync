<?php

namespace AC_SYNC\Includes\Classes\Differential\Schema;

if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Differential\Schema\Action_Wear_Schema_Interface as SchemaInterface;

class Action_Wear_Price_Schema implements SchemaInterface
{
    const SCHEMA = [
        "sku",
        "sku_camac",
        "codice_colore",
        "quantita imballo",
        "quantita cartone",
        "prezzo unita",
        "prezzo imballo",
        "prezzo cartone",
        "prezzo 10 cartoni"
    ];

    public function getSchema(): array
    {
        return self::SCHEMA;
    }
}
