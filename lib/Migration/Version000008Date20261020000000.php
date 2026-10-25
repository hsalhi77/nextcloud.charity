<?php

declare(strict_types=1);

namespace OCA\Charity\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000008Date20261020000000 extends SimpleMigrationStep {

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('cc_assets')) {
			$table = $schema->createTable('cc_assets');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('ref_payment_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('asset_type', 'string', [
				'notnull' => true,
				'length' => 50,
			]);
			$table->addColumn('date_purchased', 'date', [
				'notnull' => false,
			]);
			$table->addColumn('serial', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('imei', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('warranty', 'date', [
				'notnull' => false,
			]);
			$table->addColumn('model', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('processor', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('paid_amount', 'decimal', [
				'notnull' => true,
				'precision' => 10,
				'scale' => 2,
				'default' => 0,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['ref_payment_id'], 'cc_assets_ref_payment_idx');
		}

		return $schema;
	}
}
