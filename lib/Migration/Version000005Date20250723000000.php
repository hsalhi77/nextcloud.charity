<?php
namespace OCA\Charity\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000005Date20250723000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        if ($schema->hasTable('cc_payment')) {
            $table = $schema->getTable('cc_payment');
            if (!$table->hasColumn('description')) {
                $table->addColumn('description', 'string', ['length' => 200, 'notnull' => false]);
            }
        }

        return $schema;
    }
}
