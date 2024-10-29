<?php

namespace AC_SYNC\Includes\Classes\Differential\Entities;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Differential\Schema\Action_Wear_Schema as Schema;
use AC_SYNC\Includes\Classes\Differential\Schema\Action_Wear_Schema_Interface as SchemaInterface;

abstract class Action_Wear_Entities
{

    private $apiSchema;

    public function setApiSchema($schema): void
    {
        $this->apiSchema = $schema;
    }

    public function getUpdateTiming()
    {
        return 0;
    }

    public function execute()
    {
        return null;
    }

    private function getClassName()
    {
        $class = get_class($this);
        $class = str_replace("Action_Wear_", "", $class);
        $class = explode("\\", $class);
        $class = end($class);
        $class = strtoupper($class);
        return $class;
    }

    public function getExecutionTime(): int
    {
        return (int) get_option("_ACTIONWEAR_LAST_" . $this->getClassName() . "_UPDATE", time() - (3600 * 48));
    }

    public function updateExecutionTime(): bool
    {
        return update_option("_ACTIONWEAR_LAST_" . $this->getClassName() . "_UPDATE", time());
    }

    public function conformToSchema(SchemaInterface $schema): bool
    {
        if (!$this->apiSchema)
            throw new \Exception("No schema defined");
        $schema = new Schema($schema);
        return $schema->isValidSchema($this->apiSchema);
    }
}
