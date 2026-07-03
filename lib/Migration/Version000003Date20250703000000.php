<?php
namespace OCA\Charity\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000003Date20250703000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        if ($schema->hasTable('cc_payment')) {
            $table = $schema->getTable('cc_payment');
            if (!$table->hasColumn('payment_amount')) {
                $table->addColumn('payment_amount', 'decimal', ['precision' => 12, 'scale' => 2, 'notnull' => false, 'default' => 0]);
            }
            if (!$table->hasColumn('payment_reference')) {
                $table->addColumn('payment_reference', 'string', ['length' => 50, 'notnull' => false]);
            }
        }

        return $schema;
    }
}
