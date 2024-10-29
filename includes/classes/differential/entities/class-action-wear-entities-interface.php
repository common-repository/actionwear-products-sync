<?php

namespace AC_SYNC\Includes\Classes\Differential\Entities;

if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Differential\Schema\Action_Wear_Schema_Interface as SchemaInterface;

interface Action_Wear_Entities_Interface
{

    /**
     * Singola operazione di aggiornamento differenziale
     *
     * Effettua qualsiasi operazione necessaria al suo update differenziale, incluse chiamate API esterne e scrittura nel database.
     * Ogni singolo execute viene implementato dalla sua specifica classe
     *
     * @return bool
     * @throws Exception In caso di qualsiasi tipo di errore, sia API che di operazioni interne
     **/
    public function execute(): bool;

    /**
     * Aggiorna l'esecuzione dell'operazione al momento della sua chiamata
     *
     * @return bool
     **/
    public function updateExecutionTime(): bool;

    public function getExecutionTime(): int;

    /**
     * Effettua il match tra lo schema specifico della classe e quello passato nell'aggiornamento differenziale.
     *
     * @return bool
     **/
    public function conformToSchema(SchemaInterface $schema): bool;

    public function setApiSchema($schema): void;
    public function getUpdateTiming(): int;
}
