<?php

namespace AC_SYNC\Includes\Classes\Differential\Schema;

if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Differential\Schema\Action_Wear_Schema_Interface as SchemaInterface;

/**
 * Definisce il tipo di schema da utilizzare all'interno delle singole implementazioni della classe differenziale
 */

class Action_Wear_Schema
{

    private $schema;

    public function __construct(SchemaInterface $schema)
    {
        $this->schema = $schema;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Metodo factory che prende lo schema istanziato dal costruttore e lo confronta con quello passato al metodo
     *
     **/
    public function isValidSchema(array $schema): bool
    {
        return $this->schema->getSchema() === $schema;
    }
}
