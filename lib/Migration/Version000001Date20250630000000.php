<?php
namespace OCA\Charity\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000001Date20250630000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        // oc_cc_case
        if (!$schema->hasTable('cc_case')) {
            $table = $schema->createTable('cc_case');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true, 'length' => 9]);
            $table->addColumn('date_added', 'date', ['notnull' => false]);
            $table->addColumn('referred_by', 'string', ['length' => 255, 'notnull' => false]);
            $table->addColumn('first_name', 'string', ['length' => 100]);
            $table->addColumn('last_name', 'string', ['length' => 100]);
            $table->addColumn('id_number', 'string', ['length' => 50, 'notnull' => false]);
            $table->addColumn('city_id', 'integer', ['unsigned' => true, 'notnull' => false, 'length' => 9]);
            $table->addColumn('town', 'string', ['length' => 100, 'notnull' => false]);
            $table->addColumn('location', 'string', ['length' => 200, 'notnull' => false]);
            $table->addColumn('dob', 'date', ['notnull' => false]);
            $table->addColumn('dependants', 'integer', ['notnull' => false, 'length' => 1]);
            $table->addColumn('case_type_id', 'integer', ['unsigned' => true, 'notnull' => false, 'length' => 9]);
            $table->addColumn('description', 'string', ['length' => 2000, 'notnull' => false]);
            $table->addColumn('recommendation', 'string', ['length' => 2000, 'notnull' => false]);
            $table->addColumn('owner', 'string', ['length' => 255]);
            $table->addColumn('circle_id', 'string', ['length' => 64, 'notnull' => false]);
            $table->addColumn('created', 'datetime', ['notnull' => false]);
            $table->addColumn('updated', 'datetime', ['notnull' => false]);
            $table->addColumn('isactive', 'boolean', ['default' => true]);
            $table->setPrimaryKey(['id']);
        }

        // oc_cc_payment
        if (!$schema->hasTable('cc_payment')) {
            $table = $schema->createTable('cc_payment');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true, 'length' => 9]);
            $table->addColumn('case_id', 'integer', ['unsigned' => true, 'length' => 9]);
            $table->addColumn('payment_date', 'date', ['notnull' => false]);
            $table->addColumn('payment_receipt', 'string', ['length' => 20]);
            $table->addColumn('paid_by', 'string', ['length' => 255]);
            $table->addColumn('payment_type', 'string', ['length' => 50]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['case_id'], 'cc_payment_case');
        }

        // oc_cc_update
        if (!$schema->hasTable('cc_update')) {
            $table = $schema->createTable('cc_update');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true, 'length' => 9]);
            $table->addColumn('case_id', 'integer', ['unsigned' => true, 'length' => 9]);
            $table->addColumn('update_date', 'date', ['notnull' => false]);
            $table->addColumn('update_type_id', 'integer', ['unsigned' => true, 'notnull' => false, 'length' => 9]);
            $table->addColumn('update_by', 'string', ['length' => 255]);
            $table->addColumn('description', 'string', ['length' => 2000, 'notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['case_id'], 'cc_update_case');
        }

        // oc_cc_city
        if (!$schema->hasTable('cc_city')) {
            $table = $schema->createTable('cc_city');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true, 'length' => 9]);
            $table->addColumn('title', 'string', ['length' => 255]);
            $table->addColumn('isactive', 'boolean', ['default' => true]);
            $table->setPrimaryKey(['id']);
        }

        // oc_cc_caseType
        if (!$schema->hasTable('cc_case_type')) {
            $table = $schema->createTable('cc_case_type');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true, 'length' => 9]);
            $table->addColumn('title', 'string', ['length' => 255]);
            $table->addColumn('isactive', 'boolean', ['default' => true]);
            $table->setPrimaryKey(['id']);
        }

        // oc_cc_updateType
        if (!$schema->hasTable('cc_update_type')) {
            $table = $schema->createTable('cc_update_type');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true, 'length' => 9]);
            $table->addColumn('title', 'string', ['length' => 255]);
            $table->addColumn('isactive', 'boolean', ['default' => true]);
            $table->setPrimaryKey(['id']);
        }

        // oc_cc_attachment
        if (!$schema->hasTable('cc_attachment')) {
            $table = $schema->createTable('cc_attachment');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true, 'length' => 9]);
            $table->addColumn('object_type', 'string', ['length' => 64]);
		$table->addColumn('object_id', 'integer', ['unsigned' => true, 'length' => 9]);
            $table->addColumn('description', 'string', ['length' => 2000, 'notnull' => false]);
            $table->addColumn('data', 'string', ['length' => 2000]);
            $table->addColumn('created', 'datetime', ['notnull' => false]);
            $table->addColumn('updated', 'datetime', ['notnull' => false]);
            $table->addColumn('deleted', 'datetime', ['notnull' => false]);
            $table->addColumn('isactive', 'boolean', ['default' => true]);
            $table->addColumn('type', 'string', ['length' => 64, 'notnull' => false]);
            $table->addColumn('url', 'string', ['length' => 2000, 'notnull' => false]);
            $table->addColumn('tag', 'string', ['length' => 255, 'notnull' => false]);
            $table->addColumn('size', 'integer', ['notnull' => false]);
            $table->addColumn('name', 'string', ['length' => 255, 'notnull' => false]);
            $table->setPrimaryKey(['id']);
        }

        // oc_cc_acl
        if (!$schema->hasTable('cc_acl')) {
            $table = $schema->createTable('cc_acl');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true, 'length' => 9]);
            $table->addColumn('parentid', 'integer', ['unsigned' => true, 'default' => 0]);
            $table->addColumn('description', 'string', ['length' => 255, 'notnull' => false]);
            $table->addColumn('created', 'datetime', ['notnull' => false]);
            $table->addColumn('updated', 'datetime', ['notnull' => false]);
            $table->addColumn('deleted', 'datetime', ['notnull' => false]);
            $table->addColumn('isactive', 'boolean', ['default' => true]);
            $table->addColumn('object_id', 'integer', ['unsigned' => true, 'length' => 9]);
            $table->addColumn('object_type', 'string', ['length' => 64]);
            $table->addColumn('shareid', 'integer', ['unsigned' => true, 'notnull' => false, 'length' => 9]);
            $table->addColumn('participant', 'string', ['length' => 64]);
            $table->addColumn('type', 'integer', ['unsigned' => true, 'length' => 9]);
            $table->addColumn('permission_edit', 'boolean', ['default' => false]);
            $table->addColumn('permission_share', 'boolean', ['default' => false]);
            $table->addColumn('permission_manage', 'boolean', ['default' => false]);
            $table->addColumn('owner', 'boolean', ['default' => false]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['object_type', 'object_id'], 'cc_acl_object');
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        // Seed LOV tables
        $db = \OC::$server->getDatabaseConnection();

        $db->executeStatement("INSERT IGNORE INTO `*PREFIX*cc_city` (title, isactive) VALUES ('Unknown', 1)");

        $caseTypes = ['Educational', 'Medical', 'Project', 'Support', 'Emergency'];
        foreach ($caseTypes as $type) {
            $db->executeStatement("INSERT IGNORE INTO `*PREFIX*cc_case_type` (title, isactive) VALUES (?, 1)", [$type]);
        }

        $updateTypes = ['Visit', 'Call', 'Meeting'];
        foreach ($updateTypes as $type) {
            $db->executeStatement("INSERT IGNORE INTO `*PREFIX*cc_update_type` (title, isactive) VALUES (?, 1)", [$type]);
        }
    }
}
