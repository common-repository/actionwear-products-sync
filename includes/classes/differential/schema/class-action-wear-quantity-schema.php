<?php

namespace AC_SYNC\Includes\Classes\Differential\Schema;

if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Differential\Schema\Action_Wear_Schema_Interface as SchemaInterface;

class Action_Wear_Quantity_Schema implements SchemaInterface
{
    const SCHEMA = [
        "source_item_id",
        "source_code",
        "sku",
        "updated_at",
        "available",
        "supplier",
        "total_arrivals"
    ];
    public function getSchema(): array
    {
        return self::SCHEMA;
    }
}
