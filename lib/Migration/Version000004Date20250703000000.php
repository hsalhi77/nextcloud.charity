<?php
namespace OCA\Charity\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000004Date20250703000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        if ($schema->hasTable('cc_payment')) {
            $table = $schema->getTable('cc_payment');
            $column = $table->getColumn('case_id');
            if ($column->getNotnull()) {
                $column->setNotnull(false);
            }
        }

        return $schema;
    }
}
