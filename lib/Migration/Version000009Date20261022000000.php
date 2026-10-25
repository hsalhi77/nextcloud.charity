<?php

declare(strict_types=1);

namespace OCA\Charity\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000009Date20261022000000 extends SimpleMigrationStep {

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('cc_assets')) {
			$table = $schema->getTable('cc_assets');

			if (!$table->hasColumn('case_id')) {
				$table->addColumn('case_id', 'bigint', [
					'notnull' => false,
					'unsigned' => true,
				]);
			}

			if (!$table->hasIndex('cc_assets_case_id_idx')) {
				$table->addIndex(['case_id'], 'cc_assets_case_id_idx');
			}
		}

		return $schema;
	}
}
