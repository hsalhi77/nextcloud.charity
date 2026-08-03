<?php
namespace OCA\Charity\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000006Date20260802000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        if (!$schema->hasTable('cc_transfers')) {
            $table = $schema->createTable('cc_transfers');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true, 'length' => 9]);
            $table->addColumn('transfer_date', 'date', ['notnull' => false]);
            $table->addColumn('ref', 'string', ['length' => 100, 'notnull' => false]);
            $table->addColumn('description', 'string', ['length' => 300, 'notnull' => false]);
            $table->addColumn('amount', 'decimal', ['precision' => 12, 'scale' => 2, 'notnull' => false]);
            $table->addColumn('paid_from', 'string', ['length' => 64, 'notnull' => false]);
            $table->addColumn('paid_to', 'string', ['length' => 64, 'notnull' => false]);
            $table->setPrimaryKey(['id']);
        }

        if ($schema->hasTable('cc_payment') && !$schema->getTable('cc_payment')->hasColumn('transfer_id')) {
            $schema->getTable('cc_payment')->addColumn('transfer_id', 'integer', ['unsigned' => true, 'notnull' => false, 'length' => 9]);
        }

        return $schema;
    }
}
