<?php
namespace OCA\Charity\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000002Date20250630000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
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

        return null;
    }
}
