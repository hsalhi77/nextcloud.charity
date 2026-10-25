<?php
namespace OCA\Charity\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000007Date20261006000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        if ($schema->hasTable('cc_case') && !$schema->getTable('cc_case')->hasColumn('monthly')) {
            $schema->getTable('cc_case')->addColumn('monthly', 'boolean', [
                'notnull' => false,
                'default' => false,
            ]);
        }

        return $schema;
    }
}
