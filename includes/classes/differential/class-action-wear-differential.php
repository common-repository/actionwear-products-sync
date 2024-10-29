<?php

namespace AC_SYNC\Includes\Classes\Differential;

if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Differential\Entities\Action_Wear_Entities as Entities;

/**
 * Gestisce a livello macro le subclassi di aggiornamento differenziale
 * Ogni entità dell'aggiornamento differenziale si trova dentro entities, deve rispettare lo schema dati definito dentro schema ed estendere Entities
 */
class Action_Wear_Differential
{

    private $entity;

    public function __construct(Entities $entity)
    {
        $this->entity = $entity;
    }

    public function execute()
    {
        return $this->entity->execute();
    }

    /**
     * Qualsiasi operazione differenziale deve essere registrata qui per poter essere presa in carico dal Cronjob
     */
    public static function getRegisteredOperations(): array
    {
        return [
            "\AC_SYNC\Includes\Classes\Differential\Entities\Action_Wear_Price",
            "\AC_SYNC\Includes\Classes\Differential\Entities\Action_Wear_Quantity",
            "\AC_SYNC\Includes\Classes\Differential\Entities\Action_Wear_Product_Differential",
        ];
    }

    public function isOperationToExecute()
    {
        return time() > $this->entity->getExecutionTime() + $this->entity->getUpdateTiming();
    }
}
